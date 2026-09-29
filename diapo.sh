#!/bin/bash

function convert_video {
    vid="$1"
    vidBasename="$(basename "$vid")"
    vidName="${vidBasename%.*}"
    thumbName="media/thumbnail/$vidName.webp"
    posterName="media/poster/$vidName.webp"
    convert "$vid"[1] -strip -thumbnail '100x100^' -gravity center -extent 100x100 -stroke gray -draw "path 'M 40,40 L 60,50 L 40,60 Z' " "$thumbName"
    convert "$vid"[1] -quality 50 "$posterName"
}

export -f convert_video

find media/ -maxdepth 1 -type f -print | parallel file --mime-type | grep -E ': (image|video)/[^:]*$' > media_list

sed -n 's/: image\/[^:]*$//p' media_list > images

sed -n 's/: video\/[^:]*$//p' media_list > videos

mkdir -p media/thumbnail
mkdir -p media/poster

echo 'rendering images thumbnails...'

cat images | xargs -r -d '\n' mogrify -format webp -path media/thumbnail -strip -thumbnail '100x100^' -gravity center -extent 100x100

echo 'rendering videos thumbnails and posters...'

cat videos | parallel convert_video

echo 'generating HTML web page...'

php diapo.php > index.html
