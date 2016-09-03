git checkout master
git merge maintn1
git add *
git rm \*.sh
git rm test/\*.html
git rm test/test\*
git commit -m "cleanup"
git push origin master
git checkout maintn1