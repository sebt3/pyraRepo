#/bin/sh

mkdir -p "public/vendor/css/skins" "public/vendor/js" "public/vendor/fonts" "logs" "dbps" "public/icons"

BS="vendor/twbs/bootstrap/dist"
FA="vendor/fortawesome/font-awesome"
DB="vendor/sebt3/d3-bootstrap"
FG="vendor/components/flag-icon-css"

CSS="$BS/css/bootstrap.min.css $FA/css/font-awesome.min.css $FG/css/flag-icon.min.css"
FONTS="$BS/fonts $FA/fonts"
JS="$DB/vendor/d3.v4.min.js $DB/dist/d3-bootstrap-withextra.min.js"

for c in $CSS;do
	cp "$c" "public/vendor/css/"
done
for f in $FONTS;do
	cp $f/* "public/vendor/fonts/"
done
for j in $JS;do
	cp "$j" "public/vendor/js/"
done

cp -Rapf "$FG/flags" public/vendor/
