# Spark ✨ — Drupal 8 Example

[Spark](https://github.com/BluesparkLabs/spark) is a toolkit to develop, test and run Drupal websites. This is an example of a Sparkified Drupal 8 project. Please refer to the *Getting Started* guide in Spark's readme to learn more.

Getting this example site up and running

1. Clone Spark's repository and install packages for this example project:

        $ cd examples/drupal8
        $ composer install

2. Start Spark's containers:

        $ composer run spark containers:start

2. Install Drupal:

        $ composer run spark drupal:install

3. Visit http://localhost:7500 in your brower. (Username and password is *admin/admin*.)
