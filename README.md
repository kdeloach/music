This project is a basic web interface for uploading and playing music. The goal was to automatically sort music based on ID3 tags in an intelligent way, and to be able to create playlists based on search terms.

The syntax for search terms uses several operators such as &&, ||, !, and parentheses. Used in combination, a playlist can be formed such as:

    (The Beatles && !Revolver) || Modest Mouse
    
This works very well, but in the future I may consider replacing this with a simpler syntax.

The hardest part of this project was properly interpreting ID3 tags. Since ID3 tags are not guaranteed to be consistent or even valid, it was a little tricky. I was never fully satisfied with how this part turned out, which is why there is an option to edit meta data manually. 

In the future, I plan to make it easier to manage song meta data, simplify the search syntax, and expand the accepted file formats (there are browser limitations here since I am using the `<audio>` tag).

Other interesting aspects of this project include the use of the Amazon S3 Storage API, Google Image Search API, and jQuery UI.

![](https://github.com/kdeloach/music/raw/master/preview.png)
