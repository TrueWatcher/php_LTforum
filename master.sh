git checkout master -f
git merge maintn1
git add *
git rm -f \*.sh
git rm -f test/\*.html
git rm -f test/test\*
git commit -m "cleanup"
git push origin master
git checkout maintn1