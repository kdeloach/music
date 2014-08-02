var app = {
    init: function(artistData) {
        var me = this;
        // sometimes i want to change the hash and don't want the hash change event to execute
        this.ignoreHashChange = 0;
        // holds the progress bar timing interval 
        this.tick = null;
        this.clearTickNextInterval = false;
        // hold timing interval for rebuilding playlist
        this.queuePlaylistTick = null;
        // number of requests to rebuilt playlist
        // this is used to determine if we should ignore ajax responses
        this.numRebuildPlaylistRequests = 0;
        // record ID from datatable record set of current song
        this.currentSong = null;
        this.currentSongHash = null;
        this.currentSearch = null;
        this.currentSeed = null;
        // copy of original data returned from last search request
        // shuffle always needs to occur on original data
        this.originalData = null;
         
        // UI
        this.progressbar = $('.progressbar');
        this.btnPlay = $('.btnPlay');
        this.btnPause = $('.btnPause');
        this.btnPrevious = $('.btnPrevious');
        this.btnNext = $('.btnNext');
        this.btnShuffle = $('.btnShuffle');
        this.player = document.getElementById('player');
        this.playerHolder = $('#player-holder');
        this.preloadNotice = $('.preloadNotice');
        this.search = $('#s');
        this.searchLoading = $('.search-loading');
        
        this.setupAudioControls();
        this.setupInventory();
        this.setupPlaylist();
        this.setupUpload();      
        
        $(window).bind('hashchange', function(e) {
            if(me.ignoreHashChange > 0) {
                me.ignoreHashChange--;
                return;
            }
            me.locationChanged();
        });

        // check if there's a hash when the page first loads
        var qs = this.parseHash();
        if(qs) {
            this.currentSongHash = qs.s;
            this.search.val(qs.q);
            this.locationChanged();
            $("#tabs").tabs().tabs('select', 1);
        }
        else
        {
            $("#tabs").tabs().tabs('select', 0);
        }
    },
    setupAudioControls: function() {
        var me = this;

        this.progressbar.progressbar({ value: 0 });
        this.progressbar.click(function(e) {
            me.updateProgress(e);
        });
        this.btnPlay.click(function(e) {
            e.preventDefault();
            me.play();
        });
        this.btnPause.click(function(e) {
            e.preventDefault();
            me.pause();
        });
        this.btnPrevious.click(function(e) {
            e.preventDefault();
            me.gotoPreviousSong();
        });
        this.btnNext.click(function(e) {
            e.preventDefault();
            me.gotoNextSong();
        });
        this.btnShuffle.click(function(e) {
            e.preventDefault();
            me.shuffle();
        });
    },
    setupInventory: function() {
        var me = this;
        
        var folderFormatter = function(elCell, oRecord, oColumn, oData) {
            elCell.innerHTML += '<a href="#" class="searchable">' + oData + '</a><br />';
        };
        var artistAlbumFormatter = function(elCell, oRecord, oColumn, oData) {
            var mid = Math.round(oData.length / 2);
            var str = '<table class="album-table"><tr><td>';
            $.each(oData, function(i, obj) {
                if(i > 0 && i % mid == 0) {
                    str += '</td><td>';
                }
                str += '<p><a href="#" class="searchable">' + obj + '</a></p>';
            });
            str += '</td></tr></table>';
            elCell.innerHTML = str;
        };
        var colArtists = [
            {key:"name", label:"Folder", formatter: folderFormatter},
            {key:"albums", label:"Album", formatter: artistAlbumFormatter}
        ];
        this.dsArtists = new YAHOO.util.DataSource('inventory');
        this.dsArtists.responseType = YAHOO.util.DataSource.TYPE_JSON;
        this.dsArtists.responseSchema = {
            resultsList: 'folders',
            fields: ["name", "albums"]
        };
        this.dtArtists = new YAHOO.widget.DataTable("artists-table", colArtists, this.dsArtists, null);
        
        // setup action when artists/albums are clicked on Browse tab
        $('.searchable').live('click', function(e) {
            e.preventDefault();
            me.addToPlaylist($(this).text());
            var pos = $(this).position();
            var a = $(this).clone();
            a.css('position', 'absolute');
            // pos.top + body.paddingTop
            a.css('top', (pos.top+110) + 'px');
            a.css('left', pos.left + 'px');
            a.appendTo($('body'));
            a.animate({top: 80 + $(window).scrollTop(), left: 85}, 500, 'swing', function() {
                a.remove();
            });
        });
    },
    setupPlaylist: function() {
        var me = this;
        
        var myColumnDefs = [
            {key:"folder",label:"Folder", sortable:true},
            {key:"artist",label:"Artist", sortable:true},
            {key:"album",label:"Album", sortable:true},
            {key:"track",label:"Track", sortable:true},
            {key:"title",label:"Title", sortable:true}
        ];
    
        this.myDataSource = new YAHOO.util.DataSource();
        this.myDataSource.responseType = YAHOO.util.DataSource.JSON_ARRAY;
        this.myDataSource.responseSchema = {
            resultsList: "ResultSet.Result",
            fields: ["folder", "artist", "album", "track", "title", "hash"]
        };

        this.myDataTable = new YAHOO.widget.DataTable("songs-table", myColumnDefs, this.myDataSource, {selectionMode:'single', initialLoad: false});
        this.myDataTable.subscribe("rowMouseoverEvent", this.myDataTable.onEventHighlightRow);
        this.myDataTable.subscribe("rowMouseoutEvent", this.myDataTable.onEventUnhighlightRow);
        this.myDataTable.subscribe("rowClickEvent", this.myDataTable.onEventSelectRow);
        this.myDataTable.subscribe("rowClickEvent", function(e) {
            var rid = me.myDataTable.getSelectedRows()[0];
            var rs = me.myDataTable.getRecordSet();
            var r = rs.getRecord(rid);
            me.currentSong = rs.getRecordIndex(r);
            me.currentSongHash = r.getData('hash');
            me.queueNextSong(r.getData('hash'));
        });
        
        // setup update meta info form
        var toggleForms = function(e) {
            e.preventDefault();
            $('.edit-meta').toggleClass('hide');
            $('.edit-form').toggleClass('hide');
        };
        $('.edit-meta .link').click(toggleForms);
        $('.edit-form .cancel-button').click(toggleForms);
        $('#update-meta-form').submit(function(e) {
            e.preventDefault();
            var args = {
                q: me.search.val(),
                folder: $('#folder').val(),
                artist: $('#artist').val(),
                album:  $('#album').val(),
                track:  $('#track').val(),
                title:  $('#title').val()
            };
            $.post('update', args, function(data) {
                me.queueRebuildPlaylist(me.search.val());
                me.updateInventory();
            });
        });
        
        this.search.keyup(function(e) {
            me.queueRebuildPlaylist(me.search.val());
        });
    },
    setupUpload: function() {
        var me = this;
        $('#fileupload').fileupload({
            autoUpload: true, 
            sequentialUploads: true
        });
        // Open download dialogs via iframes, to prevent aborting current uploads:
        $('#fileupload .files a:not([target^=_blank])').live('click', function (e) {
            e.preventDefault();
            $('<iframe style="display:none;"></iframe>').prop('src', this.href).appendTo('body');
        });
        $('#fileupload').bind('fileuploadstart', function(e) {
            $("#tabs").tabs('select', 2);
        });
        $('#fileupload').bind('fileuploaddone', function(e) {
            me.updateInventory();
        });
    },
    parseHash: function() {
        if(window.location.hash.length < 1) {
            return false;
        }
        var hash = window.location.hash.substr(1);
        var parts = hash.split(',');
        var q = null;
        var s = null;
        var r = null;
        $.each(parts, function(i, part) {
            if(part.indexOf('q=') != -1) {
                q = part.substr(2);
            } else if (part.indexOf('s=') != -1) {
                s = part.substr(2);
            } else if (part.indexOf('r=') != -1) {
                r = part.substr(2);
            }
        });
        return {q: q, s: s, r: r};
    },
    locationChanged: function() {
        var me = this;
        var info = this.parseHash();
        if(!info) {
            return;
        }
        var q = info.q;
        var s = info.s;
        var r = info.r;
        if(q == me.currentSearch) {
            q = null;
        }
        if(q) {
            me.queueRebuildPlaylist(q, function() {
                if(r) {
                    me.shuffle(r);
                }
                if(s) {
                    me.queueNextSong(s);
                }
            });
        } else if (s) {
            me.queueNextSong(s);  
        }
    },
    updateProgressBarUI: function() {
        var currentTime = this.getCurrentTime();
        var duration = this.getDuration();
        if(duration <= 0) {
            return;
        }
        if(currentTime >= duration) {
            this.pause();
            this.gotoNextSong();
            return;
        }
        var value = currentTime / duration * 100;
        $('.progressbar').progressbar('option', 'value', value);
    },
    updateProgress: function(evt) {
        var bar = $(evt.currentTarget);
        var mouseX = evt.pageX;
        if(Math.min(bar.width(), mouseX) <= 0) {
            return;
        }
        var perc = (mouseX - bar.offset().left) / bar.width();
        var targetSec = this.getDuration() * perc;
        this.seekTo(targetSec);
        this.play();
    },
    play: function() {
        var me = this;
        // there is no song selected to play
        if(this.currentSong == null) {
            this.loadInitialSong();
            return;
        }
        this.btnPlay.addClass('hide');
        this.btnPause.removeClass('hide');
        this.player.play();
        clearInterval(this.tick);
        this.tick = setInterval(function() {
            me.updateProgressBarUI();
        }, 30);
    },
    pause: function() {
        this.btnPlay.removeClass('hide');
        this.btnPause.addClass('hide');
        this.player.pause();
        clearInterval(this.tick);
    },
    shuffle: function(seed) {
        this.currentSeed = seed != null ? seed+"" : Math.random()+"";
        Math.seedrandom(this.currentSeed+"");
        var dt = this.myDataTable;
        var rs = dt.getRecordSet();
        var len = rs.getLength();
        if(len == 0) {
            return;
        }
        var data = this.originalData.slice();
        var shuffledData = [];
        for(var i = 0; i < len; i++) {
            var r = Math.random();
            var k = Math.round(r * (len - i - 1));
            shuffledData.push(data.splice(k, 1)[0]);
        }
        dt.deleteRows(0, len);
        dt.addRows(shuffledData);
        this.highlightSong(this.currentSongHash);
        this.setWindowHash(this.currentSearch, this.currentSongHash, this.currentSeed);
    },
    seekTo: function(seconds) {
        this.player.currentTime = seconds;
    },
    getCurrentTime: function() {
        return this.player.currentTime;
    },
    getDuration: function() {
        return this.player.duration;
    },
    // Selects the first song in the playlist.
    // Triggered when Play button is pressed before a song is selected.
    loadInitialSong: function() {
        console.log('loadInitialSong');
        var dt = this.myDataTable;
        var rs = dt.getRecordSet();
        if(rs.getLength() == 0) {
            return;
        }
        var r = rs.getRecord(0);
        this.queueNextSong(r.getData('hash'));
    },
    showPreloadNotice: function(msg) {
        this.preloadNotice.show();
        this.preloadNotice.text(msg);
    },
    hidePreloadNotice: function() {
        this.preloadNotice.hide();
    },
    gotoPreviousSong: function() {
        var dt = this.myDataTable;
        var rs = dt.getRecordSet();
        if(rs.getLength() == 0) {
            return;
        }
        var prevSongID = this.currentSong - 1 < 0 ? rs.getLength() - 1 : this.currentSong - 1;
        var prevSong = rs.getRecord(prevSongID);
        this.queueNextSong(prevSong.getData('hash'));
    },
    gotoNextSong: function() {
        var dt = this.myDataTable;
        var rs = dt.getRecordSet();
        if(rs.getLength() == 0) {
            return;
        }
        var nextSongID = (this.currentSong + 1) % rs.getLength();
        var nextSong = rs.getRecord(nextSongID);
        this.queueNextSong(nextSong.getData('hash'));
    },
    getRecordInfoFromHash: function(hash) {
        var dt = this.myDataTable;
        var rs = dt.getRecordSet();
        var len = rs.getLength();
        for(var i = 0; i < len; i++) {
            var r = rs.getRecord(i);
            if(r.getData('hash') == hash) {
                return {index: i, record: r};
            }
        }
        return null;
    },
    addToPlaylist: function(str) {
        var oldVal = this.search.val();
        if(oldVal.length > 0) {
            oldVal += ' || ';
        }
        oldVal += str;
        this.search.val(oldVal);
        this.queueRebuildPlaylist(oldVal);
    },
    queueRebuildPlaylist: function(val, cb) {
        var me = this;
        clearTimeout(this.queuePlaylistTick);
        this.queuePlaylistTick = setTimeout(function() {
            me.rebuildPlaylist(val, cb);
        }, 1000);
        this.currentSeed = null;
    },
    queueNextSong: function(hash) {
        var me = this;
        var dt = this.myDataTable;
        if(!this.songExistsInPlaylist(hash)) {
            return;
        }
        console.log('current time', this.getCurrentTime());
        console.log('duration', this.getDuration());
        this.showPreloadNotice('Loading next song...');
        var nextSong = document.createElement('audio');
        nextSong.autoplay = 'autoplay';
        nextSong.preload = 'auto';
        nextSong.src = 'download/' + hash + '.mp3';
        nextSong.id = 'player';
        $(nextSong).bind('error', function(data) {
            console.log('error', data);
            me.showPreloadNotice('ERROR: Could not load file');
            me.preloadNotice.effect('shake', {times: 4}, 55);
        });
        $(nextSong).bind('loadeddata', function() {
            me.loadAlbumArt(hash);
            me.setWindowHash(me.currentSearch, hash, me.currentSeed);
            me.highlightSong(hash);
            me.hidePreloadNotice();
            $(me.player).remove();
            me.player = nextSong;
            me.playerHolder.append(nextSong);
            me.play();
        });
    },
    rebuildPlaylist: function(val, cb) {
        var me = this;
        this.numRebuildPlaylistRequests++;
        var thisRequest = this.numRebuildPlaylistRequests;
        this.searchLoading.show();
        $.get('search', {q: val}, function(data) {
            if(me.numRebuildPlaylistRequests > thisRequest) {
                return;
            }
            me.originalData = data;
            me.searchLoading.hide();
            var dt = me.myDataTable;
            var len = dt.getRecordSet().getLength();
            me.currentSearch = val;
            dt.deleteRows(0, len);
            dt.addRows(data);
            // to maintain continuity
            if(me.songExistsInPlaylist(me.currentSongHash)) {
                me.highlightSong(me.currentSongHash);
            } else {
                me.currentSong = null;
                me.currentSongHash = null;
            }
            me.setWindowHash(val, me.currentSongHash, me.currentSeed);
            me.search.val(val);
            if(data.length == 0) {
                dt.showTableMessage(YAHOO.widget.DataTable.MSG_ERROR, YAHOO.widget.DataTable.CLASS_ERROR);
            }
            if(cb) {
                cb();
            }
        });
    },
    songExistsInPlaylist: function(hash) {
        return this.getRecordInfoFromHash(hash) != null;
    },
    highlightSong: function(hash) {
        if(hash == null) {
            return;
        }
        var info = this.getRecordInfoFromHash(hash);
        this.currentSong = info.index;
        this.currentSongHash = hash;
        document.title = info.record.getData('title');
        var dt = this.myDataTable;
        dt.unselectAllRows();
        dt.selectRow(this.currentSong);
        // position of song row in table - 25 (#songs-table margin-top)
        $(window).scrollTop($(dt.getTrEl(this.currentSong)).position().top - 45);
    },
    // playlist query (q), current song (s), shuffle seed (r)
    setWindowHash: function(q, s, r) {
        var parts = [];
        if(q != null) {
            parts.push('q=' + q);
        }
        if(s != null) {
            parts.push('s=' + s);
        }
        /*
        // i've decided not so store random seed in the url, doesn't seem to add anything valuable to the application
        if(r != null) {
            parts.push('r=' + r);
        }
        */
        var newHash = parts.join(',');
        var oldHash = window.location.hash;
        oldHash = oldHash.length > 1 ? oldHash.substr(1) : oldHash;
        if(oldHash != newHash) {
            this.ignoreHashChange++;
        }
        window.location.hash = newHash;
    },
    loadAlbumArt: function(hash) {
        // dont want to use up my 100/day request limit during development
        //return;
        $.get('art/' + hash, function(data) {
            if(data && data.responseData && data.responseData.results) {
                if(data.responseData.results.length > 0) {
                    var n = Math.round(Math.random() * (data.responseData.results.length));
                    var img = data.responseData.results[n].tbUrl;
                    //var img = data.responseData.results[n].url;
                    $('#player-holder').css({backgroundImage: 'url(' + img + ')'});
                }
            }
        });
    },
    updateInventory: function() {
        var callback = {
            success : this.dtArtists.onDataReturnInitializeTable,
            failure : this.dtArtists.onDataReturnInitializeTable,
            scope : this.dtArtists
        };
        this.dsArtists.sendRequest('?t=' + Math.random(), callback);
    }
};
