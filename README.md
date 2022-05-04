# rpi-material-input-template

This script can be used to create a new form.dat
in this plugin (__NOTE: Replace number
in GFAPI::get_form( 1 ) with id of form chosen for export__)

``
$form = GFAPI::get_form(1);
file_put_contents(__DIR__.'/form.dat', serialize($form));``