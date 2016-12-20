git checkout master -f
rm test/.group
rm demo/.group
git rm -f test/.group
git rm -f demo/.group
git merge maintn1
git add *
git rm -f \*.sh
git rm -f test/\*.html
git rm -f test/test\*
git commit -m "cleanup"
git push origin master
git checkout maintn1