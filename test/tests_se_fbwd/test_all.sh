#!/bin/bash
rm ../.group
phpunit testAuth
rm ../test.db
rm ../*.html
phpunit test
phpunit test2
phpunit test3