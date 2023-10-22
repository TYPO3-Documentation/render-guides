#!/bin/bash

documentation_folder="Documentation"
index_file="$documentation_folder/Index.rst"
readme_rst_file="README.rst"
readme_md_file="README.md"
output_folder="output"


# Ensure the output folder exists
mkdir -p "$output_folder"

if [ -f "$index_file" ]; then
    # Index.rst exists, render from there
    vendor/bin/guides  "$documentation_folder" "$output_folder"
elif [ -f "$readme_rst_file" ]; then
    # README.rst exists, render only that file
    vendor/bin/guides  "." "$output_folder" --input-file="$readme_rst_file"
    mv "$output_folder/README.html" "$output_folder/index.html"
elif [ -f "$readme_md_file" ]; then
    # README.md exists, render only that file
    vendor/bin/guides  "." "$output_folder" --input-file="$readme_md_file" --input-format=md
    mv "$output_folder/README.html" "$output_folder/index.html"
else
    echo "No suitable documentation file found. Please create a file called Documentation/Index.rst or README.rst or README.md in the directory you are running this file in."
fi
