#!/bin/bash
wp-env run tests-wordpress chmod -c ugo+w /var/www/html
wp-env run tests-cli wp rewrite structure '/%postname%/' --hard

wp-env run tests-cli wp user create editor editor@eightdayweek.com --user_pass=password --role=editor

