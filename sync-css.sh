#!/bin/bash
NAME=tembo
SRC=~/bga/bga-$NAME/ # with trailing slash

# Sass
sass "$NAME.scss" "$NAME.css"

# Copy
rsync $SRC/$NAME.css ~/bga/studio/$NAME/
rsync $SRC/$NAME.css.map ~/bga/studio/$NAME/
