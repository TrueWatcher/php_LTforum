git checkout master -f
git merge maintn1
rm test/.group
rm demo/.group
git rm -f test/.group
git rm -f demo/.group
rm -r test/junk
git rm -fr test/junk
rm -r test/tests
git rm -fr test/tests
git rm -f \*.sh
git rm -f test/\*.html
git add --all *

