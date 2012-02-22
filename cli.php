<?php

return array(
    'jork' => array(
        'descr' => 'JORK ORM command-line tools',
        'commands' => array(
            'build-schema' => array(
                'descr' => 'Re-generates the internal representation of the mapping schema.

It is recommended to run this command after every model schema change.',
                'callback' => array(cyclone\jork\schema\SchemaBuilder::factory(), 'build_schema')
            ),
            'validate-schema' => array(
                'descr' => 'Validates the model schemas.',
                'callback' => array(cyclone\jork\schema\SchemaValidator::inst(), 'validate')
            )
        )
    )
);