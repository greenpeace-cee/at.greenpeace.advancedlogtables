# at.greenpeace.advancedlogtables

[![CircleCI](https://circleci.com/gh/greenpeace-cee/at.greenpeace.advancedlogtables.svg?style=svg)](https://circleci.com/gh/greenpeace-cee/at.greenpeace.advancedlogtables)

Advanced settings for detailed logging (log tables), largely based on [nz.co.fuzion.innodbtriggers](https://github.com/eileenmcnaughton/nz.co.fuzion.innodbtriggers).

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.6+
* CiviCRM 5.13+
  * Compatible with CiviCRM 5.7 using these core patches:
    * [PR-13441](https://patch-diff.githubusercontent.com/raw/civicrm/civicrm-core/pull/13441.patch)
    * [PR-13462](https://patch-diff.githubusercontent.com/raw/civicrm/civicrm-core/pull/13462.patch)

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl at.greenpeace.advancedlogtables@https://github.com/greenpeace-cee/at.greenpeace.advancedlogtables/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/greenpeace-cee/at.greenpeace.advancedlogtables.git
cv en advancedlogtables
```

## Configuration

Advanced log tables ships with a default configuration based on the [nz.co.fuzion.innodbtriggers](https://github.com/eileenmcnaughton/nz.co.fuzion.innodbtriggers)
extension, enabling the InnoDB storage engine, table compression and creating
various log table indexes to speed up change log reports.

**The default configuration works well for most sites and should only be
changed if necessary and after extensive testing. You can skip this section
if you're happy with the defaults.**

Advanced log tables supports the following settings:

| **Name**                                | **Default**                            | **Description**                                                                                                                                                                                                                                        |
|-----------------------------------------|----------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `advancedlogtables_storage_engine`        | `InnoDB`                                 | MySQL storage engine for log tables. `InnoDB` is a good choice for most sites.                                                                                                                                                                           |
| `advancedlogtables_storage_engine_config` | `ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=4` | MySQL storage engine configuration for log tables. Defaults to enabling table compression, which trades smaller disk usage for a small increase in CPU usage that is acceptable for most sites. Use `ROW_FORMAT=DYNAMIC` to disable table compression. |
| `advancedlogtables_index_contact`         | `TRUE`                                   | Whether to create indexes on all columns referencing `civicrm_contact.id`. Significantly improves the performance of change log reports.                                                                                                               |
| `advancedlogtables_index_conn_id`         | `TRUE`                                   | Whether to create indexes on connection ID columns. Significantly improves the performance of change log reports.                                                                                                                                      |
| `advancedlogtables_index_log_date`        | `TRUE`                                   | Whether to create indexes on log date columns. Significantly improves the performance of change log reports.                                                                                                                                           |
| `advancedlogtables_index_id`              | `TRUE`                                   | Whether to create indexes on ID columns. Significantly improves the performance of change log reports.                                                                                                                                                 |

Settings can be changed using the API, for example with the APIv3 explorer or
the command line:

    cv api Setting.create advancedlogtables_storage_engine="MyISAM"

The recommended settings are highly dependent on the size of your site as well
as your performance and durability requirements. InnoDB with the default
configuration represents a safe default with reasonable compression. The TokuDB
engine with engine configuration set to `COMPRESSION=tokudb_zlib` has been
known to work quite well for larger sites, but requires additional software to
be installed on the database server as well as configuration and tuning.

Additionally, you can select if you want to **exclude** (or **include** with negation) specific
tables from the logging functionality.

This all can be done in the administration page of this extension on path `/civicrm/admin/advancedlogtables/config?reset=1`.
Set your tables (optionally of course) that you want to exclude (or include, using the negate checkbox) just save the form.

Once the form has been saved, you will need to recreate all the triggers of your site.
You ca simply issue this URL : `/civicrm/menu/rebuild?reset=1&triggerRebuild=1` as seen [here](https://docs.civicrm.org/sysadmin/en/latest/troubleshooting/#trigger-rebuild)

Or if you're running Drupal and have drush installed, you can issue: `drush civicrm-sql-rebuild-triggers` which will rebuild the triggers.

Note: Please test it in a test environment before running it in production!

## Usage

This extension does not automatically enable detailed logging or change the
storage engine of existing log tables. When you install the extension or change
any of its settings, and detailed logging was enabled at any point on your site,
you will need to run the `System.updatelogtables` API call to migrate existing
log tables. If you're enabling logging for the first time after installing this
extension, you may skip this step, but it is safe to run it regardless if you're
uncertain.

It is highly recommended to run `System.updatelogtables` in a test environment
with a full copy of your database initially, and to perform a database backup
before deployment. MySQL will rewrite all log tables during this operation,
which may cause performance issues or deadlocks while the process is running.

For larger sites, it is recommended to run the migration via command line to
avoid PHP timeouts in the middle of the migration:

    cv api System.updatelogtables

If you want to change the engine configuration (`advancedlogtables_storage_engine_config`)
after logging has already been enabled, you will need to add the
`updateChangedEngineConfig` parameter:

    cv api System.updatelogtables updateChangedEngineConfig=1

> **Note:** `updateChangedEngineConfig` is only available in CiviCRM 5.15 and later.
