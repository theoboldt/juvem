#JUVEM
Juvem is a symfony based web application to manage events and newsletters. 

## Features

### Events
Create events so that people can request participation on them. Originally *Juvem* was created to make parents able to register their kids for participation on special events, but can be used for regular events as well.

#### Participation handling 
Participations can be confirmed, withdrawn or rejected. It is also possible to enable auto-confirmation for events if you do not want to confirm one by one.
 
Create excel exports containing participant lists and write emails which are sent to all participants.  

#### Custom fields
Beside the default fields like name, age or phone numbers you can define your own fields to be acquired on each participation requests. Those fields can be of type text or selection (single/multiple). You enable those fields on each event you want to acquire this data.

### Newsletter
People can register themselves in order to receive the newsletter. For each newsletter, you have to define an age range specifying for people of which age this newsletter is directed to. People can select the age range they want to be notified about.

## Requirements
Juvem is a symfony 3.2 based application.
 
* PHP 7 and above
* MySQL database

## Installation and deployment
* Checkout project
* Navigate into project folder
* Install PHP composer dependencies with `composer install`. If you did not configure configs parameters.yml file before, you may be asked now to do so.
* Setup database by executing `./app/console doctrine:schema:create`
* Install npm dependencies with `npm install`
* In order to have CSS and JS build, you need to execute grunt task `grunt deploy`

