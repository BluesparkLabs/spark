# Spark ✨

Toolkit to develop, test and run PHP applications.

Spark provides a turnkey Docker-based **environment** for development and continuous integration. It ships **commands** to create anonymized database exports, execute test suites, initialize a Solr index etc. Spark simply needs to be added as your project's **dependency**, and after some minimal configuration steps you're ready to go.

["Concerning toolkits"](https://blog.kentcdodds.com/concerning-toolkits-4db57296e1c3), an article published by [Kent C. Dodds](https://github.com/kentcdodds) had been a great inspiration for architecting Spark.

## Roadmap

* We're in the middle of implementing key database interactions in order to be able to use Spark for creating GDPR-compliant, anonymized database exports. ([See our board here.](https://github.com/BluesparkLabs/spark/projects/1))
* After the features around interacting with the database we'll turn to getting prepared for our first alpha release, which will introduce a more flexible way for defining the required services for project environments.


## Getting Started — How to Sparkify your project

Check out the [Drupal 8 example project](https://github.com/BluesparkLabs/spark/tree/master/examples/drupal8).

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

To learn about how to write your project-specific configuration, please refer to our [`.spark.example.yml` file](https://github.com/BluesparkLabs/spark/blob/master/.spark.example.yml).

**5. Optional: Create a file named `.spark.local.yml` in your project's root.** This will be your environment-specific configuration that Spark will use. Do not commit this file to your repository. If you want to leverage environment-specific configuration for CI builds or in your hosting environment, the recommended way is to keep these files in your repository named specifically, i.e. `.spark.local.ci.yml`, and then ensure you have automation in place that renames it to `.spark.local.yml` in the appropriate environment.

To learn about how to write your project-specific configuration, please refer to our [`.spark.example.yml` file](https://github.com/BluesparkLabs/spark/blob/master/.spark.local.example.yml).

### Recommended `composer.json` bits

See the [Drupal 8 example project's `composer.json` file](https://github.com/BluesparkLabs/spark/blob/master/examples/drupal8/composer.json).

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

**2.** In case you're working with a **Drupal site, use [drupal-composer/drupal-scaffold](https://packagist.org/packages/drupal-composer/drupal-scaffold) to install and update files that are outside of the `core`** folder and which are not part of the [drupal/core](https://packagist.org/packages/drupal/core) package. This Composer plugin will take care of the files whenever you install or update `drupal/core`, but to run it manually, you can add a script to your `composer.json`:

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
|`drupal`|(Being deprecated.) Create backup and upload to an Amazon S3 bucket, ensure `files` directory and `settings.php` files, install Drupal|
|`mysql`|Import or export database. (Will eventualy deprecate `db` command group.)
|`solr`|Initialize a Solr core with configuration for Drupal, check its availability|
|`test`|Execute test suite|

## Commands

Notes:

* Commands will be documented here as they become ready for prime time.
* ⚠️ When using command-line arguments, you need to include a double-dash (`--`) before your arguments. E.g. `composer run spark mysql:dump -- --non-sanitized`. (See the [reason for this](https://github.com/BluesparkLabs/spark/issues/10#issuecomment-424646525) and the [proposed solution](https://github.com/BluesparkLabs/spark/issues/36).)

### `mysql:dump`

Exports database to a file. By default the file is placed into the current folder and data is sanitized based on the sanitization rules in `.spark.yml.` The following command-line arguments are optional.

|Argument|Description|
|--------|-----------|
|`--non-sanitized`|Produces a non-sanitized data export.|
|`--destination`|Directory to where the export file will be placed. Can be an absolute or a relative path.|