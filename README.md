# JUVEM
Juvem is a symfony based web application to manage events and newsletters.

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg?style=flat-square)](https://php.net/) [![license](https://img.shields.io/github/license/mashape/apistatus.svg?style=flat-square)]()
![Dependencies and Tests](https://github.com/theoboldt/juvem/workflows/Dependencies%20and%20Tests/badge.svg)
[![Maintainability](https://api.codeclimate.com/v1/badges/a41bc804ab7172d930ce/maintainability)](https://codeclimate.com/github/theoboldt/juvem/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/a41bc804ab7172d930ce/test_coverage)](https://codeclimate.com/github/theoboldt/juvem/test_coverage)
[![codecov](https://codecov.io/gh/theoboldt/juvem/branch/master/graph/badge.svg)](https://codecov.io/gh/theoboldt/juvem)

## Features

### Events
Create events so that people can request participation on them. Originally *Juvem* was created to make parents able to register their kids for participation on special events, but can be used for regular events as well.

#### Participation handling
Participations can be confirmed, withdrawn or rejected. It is also possible to enable auto-confirmation for events if you do not want to confirm one by one.

Juvem is able to send emails when somebody requested for participation and when a participation is confirmed. Administrators can also send emails to all participants of an event.

Create excel exports containing participant lists and write emails which are sent to all participants.

Create and handle attendance lists, check attendance on multiple devices at the same time.

Add notes or comments to participations or participants to share information with your colleagues.

#### Custom fields
Beside the default fields like name, age or phone numbers you can define your own fields to be acquired on each participation requests. Those fields can be of type text or selection (single/multiple). You enable those fields on each event you want to acquire this data.

### Newsletter
People can register themselves in order to receive the newsletter. For each newsletter, you have to define an age range specifying for people of which age this newsletter is directed to. People can select the age range they want to be notified about.

![Homepage Screenshot](app/assets/screenshots/homepage.png)
Presents your events on homepage, providing more information for logged in admins

![Event management](app/assets/screenshots/event_admin_detail.png)
A lot of things to do in event details for admin, providing lists, statistics, exports, weather data...

![Public participation form](app/assets/screenshots/event_public_participation.png)
Public participation form, providing autofill for registered users

![Participation history](app/assets/screenshots/event_admin_statistics.png)
See how early participants register for events

![Participants dependencies](app/assets/screenshots/event_admin_graph.png)
Detect dependencies between participants, easily distribute them in groups

![Excel export generation](app/assets/screenshots/event_admin_export.png)
Create customized excel export 

![Custom fields overview](app/assets/screenshots/formula_overview.png)
Create custom fields to ask parents for special data

![Formula editor](app/assets/screenshots/formula_editor.png)
Configure formulas calculating participation price depending on chosen options when participating

![Newsletter creation](app/assets/screenshots/newsletter.png)
Create newsletters

![Participation price management](app/assets/screenshots/participant_price.png)
Manage participation prices and payments

## Requirements
Juvem is a symfony 4.4 based application.

### Production
* PHP 8.1
* MySQL/MariaDB InnoDB database
* ~ 175 MB disk space (including dependencies)

### Development/Deployment
* PHP 8.1
* MySQL/MariaDB InnoDB database
* Npm/Grunt for css/js deployment, having `grunt-cli` installed globally
* Having sass gem installed
* ~ 225 MB disk space (including dev/deployment dependencies, node modules)

## Installation and deployment
* Checkout project
* Navigate into project folder
* Install PHP composer dependencies with `composer install`. If you did not configure configs parameters.yml file before, you may be asked now to do so.
* Setup database by executing `./app/console doctrine:schema:create`
* If `grunt-cli` is not yet installed, install it via `npm install -g grunt-cli`
* If sass gem is not yet installed, install it via `gem install sass`
* Install npm dependencies with `npm install`
* In order to have CSS and JS build, you need to execute grunt task `grunt deploy`

