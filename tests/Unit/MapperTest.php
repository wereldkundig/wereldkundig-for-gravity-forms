<?php

use Wereldkundig\GravityForms\Addon;
use Wereldkundig\GravityForms\Mapper;

function wk_gf_test_field(int $id, string $type, string $label = '', array $properties = []): GF_Field {
    // Gravity Forms gives every form field as a GF_Field object.
    $field = new GF_Field();
    foreach (array_merge([
        'id'    => $id,
        'type'  => $type,
        'label' => $label,
    ], $properties) as $key => $value) {
        $field->{$key} = $value;
    }

    return $field;
}

/**
 * The add-on, with a feed that maps no fields.
 */
function wk_gf_mapper_addon(): Mockery\MockInterface {
    $addon = Mockery::mock(Addon::class);
    $addon->shouldReceive('get_field_map_fields')->andReturn([]);

    return $addon;
}

it('auto-maps the first Gravity Forms email and name fields', function () {
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_auto_map_fields'] = true;

    $addon = wk_gf_mapper_addon();
    $form  = [
        'fields' => [
            wk_gf_test_field(1, 'email', 'Email'),
            wk_gf_test_field(2, 'name', 'Name', ['inputs' => [['id' => '2.3'], ['id' => '2.4'], ['id' => '2.6']]]),
        ],
    ];
    $entry = [
        '1'   => 'reader@example.com',
        '2.3' => 'Vincent',
        '2.4' => 'van',
        '2.6' => 'Gogh',
    ];

    expect((new Mapper($addon))->map($form, $entry, ['meta' => []]))->toBe([
        'email'       => 'reader@example.com',
        'first_name'  => 'Vincent',
        'middle_name' => 'van',
        'last_name'   => 'Gogh',
    ]);
});

it('uses explicit feed mappings and auto-maps missing mappings', function () {
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_auto_map_fields'] = true;

    $addon = Mockery::mock(Addon::class);
    $addon->shouldReceive('get_field_map_fields')
        ->once()
        ->andReturn(['email' => '9', 'first_name' => '', 'last_name' => '']);
    $addon->shouldReceive('get_field_value')
        ->once()
        ->with(Mockery::type('array'), Mockery::type('array'), '9')
        ->andReturn('mapped@example.com');

    $form = [
        'fields' => [
            wk_gf_test_field(1, 'email', 'Email'),
            wk_gf_test_field(2, 'text', 'Voornaam'),
            wk_gf_test_field(3, 'text', 'Achternaam'),
        ],
    ];
    $entry = [
        '1' => 'auto@example.com',
        '2' => 'Ada',
        '3' => 'Lovelace',
    ];
    $feed = ['meta' => []];

    expect((new Mapper($addon))->map($form, $entry, $feed))->toBe([
        'email'       => 'mapped@example.com',
        'first_name'  => 'Ada',
        'middle_name' => '',
        'last_name'   => 'Lovelace',
    ]);
});

it('maps every subscriber field configured in the feed', function () {
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_auto_map_fields'] = true;

    $addon = Mockery::mock(Addon::class);
    $addon->shouldReceive('get_field_map_fields')
        ->once()
        ->andReturn(['email' => '', 'middle_name' => '3', 'company' => '4']);
    $addon->shouldReceive('get_field_value')
        ->andReturnUsing(static fn ($form, $entry, $field_id): string => $entry[$field_id]);

    $form = [
        'fields' => [
            wk_gf_test_field(1, 'email', 'Email'),
            wk_gf_test_field(2, 'text', 'Voornaam'),
            wk_gf_test_field(3, 'text', 'Tussen'),
            wk_gf_test_field(4, 'text', 'Bedrijf'),
        ],
    ];
    $entry = ['1' => 'reader@example.com', '2' => 'Vincent', '3' => 'van', '4' => ' <b>Acme</b> '];

    expect((new Mapper($addon))->map($form, $entry, ['meta' => []]))->toBe([
        'email'       => 'reader@example.com',
        'first_name'  => 'Vincent',
        'middle_name' => 'van',
        'last_name'   => '',
        'company'     => 'Acme',
    ]);
});

it('auto-maps a tussenvoegsel text field', function () {
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_auto_map_fields'] = true;

    $form  = ['fields' => [wk_gf_test_field(1, 'text', 'Tussenvoegsel')]];

    expect((new Mapper(wk_gf_mapper_addon()))->map($form, ['1' => 'de'], ['meta' => []])['middle_name'])->toBe('de');
});

it('does not auto-map by default', function () {
    $form = ['fields' => [wk_gf_test_field(1, 'email', 'Email')]];

    expect((new Mapper(wk_gf_mapper_addon()))->map($form, ['1' => 'reader@example.com'], ['meta' => []]))->toBe([
        'email'       => '',
        'first_name'  => '',
        'middle_name' => '',
        'last_name'   => '',
    ]);
});
