# RAIAS

RAIAS, the Remote Artificial Intelligence Accounting System, is a PHP application.

## What RAIAS does

A company deploys RAIAS on its own server and points RAIAS at the records the company already keeps. RAIAS presents the financial statements in IFRS form and in GAAP form. RAIAS computes the ratios a reader asks for first: net margin, the current ratio and leverage. RAIAS tracks trends period by period and compares what happened against the budget. RAIAS groups customers into cohorts by the period they first bought. RAIAS reads records through a connector layer.

## How to run RAIAS

Set the environment variable `RAIAS_BASE` to `/raias/`. Then serve RAIAS:

```
php -S 127.0.0.1:8121 -t showroom showroom/index.php
```

Run the RAIAS tests with:

```
php tests/run.php
```

## Layout

- `showroom/` holds the pages RAIAS serves. `showroom/index.php` routes each request.
- `src/` holds the RAIAS code: the statement builders for IFRS and GAAP, the ratio calculators, the trend and budget comparison, the cohort grouping and the connector.
- `tests/` holds the RAIAS test suite. `php tests/run.php` runs every test.

## The connector

RAIAS reads records through a connector layer. The connector holds a list of sources and each source names its own address. Each source implements the same interface, so the connector can ask any source for the records of a given period. The connector writes the records from every source into a ledger, and the ledger feeds the statements, the ratios, the trends and the cohorts. RAIAS publishes no data of its own. The company keeps the source.

## Tests

The RAIAS suite runs without Composer and without a vendor directory.