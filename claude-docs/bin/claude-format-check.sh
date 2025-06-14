#!/bin/bash

# Format check and fix script for Claude
# Fixes files according to format rules in CLAUDE.md

if [ $# -eq 0 ]; then
    echo "Usage: $0 <file1> [file2] ..."
    echo "Example: $0 CLAUDE.md docs/*.md"
    exit 1
fi

for file in "$@"; do
    if [ ! -f "$file" ]; then
        echo "File not found: $file"
        continue
    fi

    echo "Checking: $file"

    # Remove trailing spaces
    sed -i 's/[[:space:]]*$//' "$file"
    echo "  - Removed trailing spaces"

    # Check and add final newline
    if [ $(tail -c 1 "$file" | wc -l) -eq 0 ]; then
        echo >> "$file"
        echo "  - Added final newline"
    else
        echo "  - Final newline OK"
    fi
done

echo "Format check completed."
