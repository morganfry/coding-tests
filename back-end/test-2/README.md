# Back end Drupal Coding Test 2

To set up the project please fork this GitHub repository to your personal account.

We use Docker Desktop and DDEV for Drupal our local development. If you prefer a different local development enviroment, please use what you are comfortable with or already have set up.

To install Docker and DDEV please follow their instructions here:
* [Docker](https://ddev.readthedocs.io/en/latest/users/install/docker-installation/)
* [DDEV](https://ddev.readthedocs.io/en/latest/users/install/ddev-installation/)

Once DDEV and Docker are set up you can start the project by running the following commands:

```
cd back-end/test-2
ddev start
ddev composer install
ddev drush site:install --account-name=admin --account-pass=admin -y
ddev launch
```

To stop DDEV run:
```
ddev poweroff
```

1. Install a new Drupal instance with standard profile using ddev and composer.
2. Create content type Movies with following fields -
  a. Title
  b. Director  (referencing the Director content type) - Multi-valued
  c. Actor (referencing the Actor content type) - Multi-valued
  d. Category (referencing a "Categories" taxonomy vocabulary) - Multi-valued
  e. Year of release
3. Create content types for Directors and Actors with fields Title and Bio respectively.
4. Add terms such as "Action," "Drama," "Comedy," etc. for Categories vocabulary.
5. Create a custom module to display and store movie ratings using css/js.
6. Create a rating submission form with a select field and submit button. Users can select from values 1 to 5 stars for the movie.
7. Use the block to display average ratings of the movie using the data added in the database and also add the form to the same block.
  Store ratings along with user IP.
8. Create a view page to display the list of all movies with filters - category, actors, directors and star ratings.
9. On the left sidebar, list down top 5 popular movies (with most votes) and also list top 5 highest rated movies.

Bonus points for
  1. Adding flood control to the ratings submission form to ensure there are no bots submissions.
  2. Adding QR code on each movie page which takes the user to its trailer in Youtube. The text should read - Scan the QR code to watch the trailer of this movie. Youtube link can be stored as a separate field.


Once you’re done, double and triple check your code, including code style to make sure it is up to Drupal standards. Add notes to the module's README.md with a high level overview on your approach and any commands that need to be run for the setup. Also, make sure nothing is hard-coded and you should use configurations where ever required. Also, we can add .gitignore file as per Drupal recommendations.

Email a link back to your repository for us to review. We should be able to clone it locally, run the ddev commands above plus any commands in the module's README.md file to see your work.

You have 48 hours from now to return this exercise back to us. Good luck, and feel free to reach out with any questions!

# Installation for testing completed submission

Run ddev site install with current configuration, then migrate in the demo content as follows:

```
cd back-end/test-2
ddev start
ddev composer install
ddev drush site:install minimal --existing-config --account-name=admin --account-pass=admin -y
ddev drush mim --group=movies
ddev launch
```
