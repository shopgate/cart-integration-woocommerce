#!/bin/sh

ZIP_FILE_NAME=shopgate-woocommerce-integration.zip

rm -rf src/vendor release/package $ZIP_FILE_NAME
mkdir release/package && mkdir release/package

composer install -vvv --no-dev

rsync -av --exclude-from './release/exclude-filelist.txt' ./src/ release/package
rsync -av ./README.md release/package
rsync -av ./LICENSE.md release/package
rsync -av ./CONTRIBUTING.md release/package
rsync -av ./CHANGELOG.md release/package

cd release/package;
zip -r ../../$ZIP_FILE_NAME .
