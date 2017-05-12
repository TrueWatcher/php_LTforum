#!/bin/bash
rm ../.group
rm ../test.db
cp "test.db" "../test.db"
phpunit testUnitAdmin
phpunit testUnitAccCntr
phpunit testAuth
#rm ../test.db
rm ../*.html
phpunit test
phpunit test2
phpunit test3
rm ../*.html