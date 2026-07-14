# Coding Tests
Please find below information on our front and back end take home coding tests.  Please fork the repository and let us know the link of where we can evaulate your results.

* [Back-end Drupal coding test information](back-end)
* [Front-end coding test information](front-end/README.md)

#
# Install instructions for testing this submission
### Back End
```
  cd back-end/test-2
  ddev start
  ddev composer install
  ddev drush site:install minimal --existing-config --account-name=admin --account-pass=admin -y
  ddev drush mim --group=movies
  ddev launch
  ```
  ### Front End
  ```
  cd front-end
  npm instsall
  npm run dev
  ```
