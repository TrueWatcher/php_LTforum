git checkout master -f
git merge maintn1
rm test/.group
rm demo/.group
git rm -f test/.group
git rm -f demo/.group
rm test/junk
git rm -f test/junk
git add *
git rm -f \*.sh
git rm -f test/\*.html
git rm -f test/test\*
git commit -m "cleanup"
git push origin master
git checkout maintn1