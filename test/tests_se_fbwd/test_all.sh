#!/bin/bash
rm ../.group
cp "test.db" "../test.db"
phpunit testAuth
rm ../test.db
rm ../*.html
phpunit test
phpunit test2
phpunit test3