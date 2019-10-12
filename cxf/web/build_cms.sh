#!/bin/sh
svn up $2
svn revert -R $2
bin/update-site -Dsite.output=$2

