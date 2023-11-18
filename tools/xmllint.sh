#!/bin/bash

set -e

schema="vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd"
directory="."

for file in $(find "$directory" -type f -name "guides.xml"); do
    xmllint --noout --schema "$schema" "$file"
done
