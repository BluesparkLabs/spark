# Spark ✨

Toolkit to develop, test and run Drupal websites.

## Motivation, goals

The developer team at [Bluespark](https://www.bluespark.com) have been discussing the need of a standardized local environment for a long time. We've tried a few directions over the past year or so, including an experiment with [Habitat](https://www.habitat.sh) or just relying on Docker Compose-centric solutions (i.e. [isholgueras/docker-lamp](https://github.com/isholgueras/docker-lamp) or [wodby/docker4drupal](https://github.com/wodby/docker4drupal)). The latter turned out to be a great approach, however, we had only used it as a starting point, so its maintenance across projects became challenging. Along the way we also started adding utility functions to our various wrapper scripts, so the need for organizing those in an upstream repository became clear.

The motivation behind Spark is to provide an **environment** for local development and CI, and to ship **commands** we can standardize accross projects, e.g. creating anonymized database dumps, executing test suites, initializing a Solr index etc. Doing so in a way that only requires projects to include Spark as a simple **dependency**, while keeping the required configuration at the the minimum.

["Concerning toolkits"](https://blog.kentcdodds.com/concerning-toolkits-4db57296e1c3), an article published by [Kent C. Dodds](https://github.com/kentcdodds) had been a great inspiration for architecting Spark.

## Roadmap

The project is at an early stage, some directions are still definitely being shaped. Here are some important highlights from our roadmap. (Please see [the issues](https://github.com/BluesparkLabs/spark/issues) for more.)

* Our immediate, short-term focus is on shipping a command to create sanitized, GDPR-compliant database dumps: [#11](https://github.com/BluesparkLabs/spark/issues/11);
* Drupal 7 support is coming soon: [#15](https://github.com/BluesparkLabs/spark/issues/15);
* We're currently evaluating whether we can/should replace the environment handling and rely on [Lando](https://docs.devwithlando.io): [#4](https://github.com/BluesparkLabs/spark/issues/4);
* There is a discussion about the viability of turning Spark into a global dependency, as opposed to requiring it on the project-level: [#5](https://github.com/BluesparkLabs/spark/issues/5).

## Getting Started — How to Sparkify your Drupal project

Check out the example project: [spark-example-drupal8](https://github.com/BluesparkLabs/spark-example-drupal8).

Here are the main steps outlined.

**1. Add Spark as a dependency:**

        $ composer require bluesparklabs/spark

**2. Define a new script in your `composer.json`:**

```javascript
"scripts": {
  "spark": "SPARK_WORKDIR=`pwd` robo --ansi --load-from vendor/bluesparklabs/spark"
}
```

**3. Add autoload information to your `autoload` field in your `composer.json`:**

```javascript
"autoload": {
    "psr-4": {
        "BluesparkLabs\\Spark\\": "./vendor/bluesparklabs/spark/src/"
    }
},
```

**4. Create a file named `.spark.yml` in your project's root.** This will be your project-specific configuration that Spark will use.

To learn about how to write your configuration, please refer to the [spark-example-drupal8 project's `.spark.yml` file](https://github.com/BluesparkLabs/spark-example-drupal8/blob/master/.spark.yml).

### Recommended `composer.json` bits

See the [spark-example-drupal8 project's `composer.json` file](https://github.com/BluesparkLabs/spark-example-drupal8/blob/master/composer.json).

**1.** Composer by default installs all packages under a directory called `./vendor`. **Use [`composer/installers`](https://packagist.org/packages/composer/installers) to define installation destinations** for Drupal modules, themes etc. Example configuration in `composer.json`:

```javascript
"extra": {
    "installer-paths": {
        "web/core": ["type:drupal-core"],
        "web/libraries/{$name}": ["type:drupal-library"],
        "web/modules/contrib/{$name}": ["type:drupal-module"],
        "web/profiles/contrib/{$name}": ["type:drupal-profile"],
        "web/themes/contrib/{$name}": ["type:drupal-theme"],
        "drush/contrib/{$name}": ["type:drupal-drush"]
    }
}
```

**2.** In case your site is Drupal 8 ([Drupal 7 support is coming](https://github.com/BluesparkLabs/spark/issues/15)), **use [drupal-composer/drupal-scaffold](https://packagist.org/packages/drupal-composer/drupal-scaffold) to install and update files that are outside of the `core`** folder and which are not part of the [drupal/core](https://packagist.org/packages/drupal/core) package. This Composer plugin will take care of the files whenever you install or update `drupal/core`, but to run it manually, you can add a script to your `composer.json`:

```javascript
"scripts": {
    "drupal-scaffold": "DrupalComposer\\DrupalScaffold\\Plugin::scaffold",
},
```

**3.** Spark has a command, `drupal:files`, to ensure the `files` folder exists with the right permissions, and that there's a `settings.php` file and a `settings.spark.php` which currently holds Spark's Docker-specific configuration, i.e. database connection etc. You may want to add this command to your `scripts` field in your `composer.json`, so that Composer executes it when packages are installed or updated:

```javascript
"scripts": {
    "post-install-cmd": "composer run spark drupal:files",
    "post-update-cmd": "composer run spark drupal:files",
}
```

## Usage

This is how you can run a Spark command:

    $ composer run spark <command>

Tip: Set up `spark` as a command-line alias to `composer run spark`.

To list all available commands, just issue `$ composer run spark`. Here is a high-level overview of what you can currently do with Spark:

|Command namespace|Description|
|-----------------|-----------|
|`drush`|Execute Drush commands|
|`containers`|Manage a Docker-based environment|
|`db`|Drop or import database, check its availability|
|`drupal`|Create backup and upload to an Amazon S3 bucket, ensure `files` directory and `settings.php` files, install Drupal|
|`solr`|Initialize a Solr core with configuration for Drupal, check its availability|
|`test`|Execute test suite|
