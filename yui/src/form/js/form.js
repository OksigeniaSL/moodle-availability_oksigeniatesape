/**
 * JavaScript for the form of the restriction by Tesapé: the class lights the lantern of a place, or the hero of the
 * student reaches a level.
 *
 * @module moodle-availability_oksigeniatesape-form
 */
// The name Moodle gives to the module of a condition.
// eslint-disable-next-line camelcase
M.availability_oksigeniatesape = M.availability_oksigeniatesape || {};

/**
 * @class M.availability_oksigeniatesape.form
 * @extends M.core_availability.plugin
 */
M.availability_oksigeniatesape.form = Y.Object(M.core_availability.plugin);

/**
 * The places of the mission of the course, in their order: code and name (already escaped).
 *
 * @property places
 * @type Array
 */
M.availability_oksigeniatesape.form.places = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} places Objects with code and name
 */
M.availability_oksigeniatesape.form.initInner = function(places) {
    this.places = places;
};

M.availability_oksigeniatesape.form.getNode = function(json) {
    var str = function(key) {
        return M.util.get_string(key, 'availability_oksigeniatesape');
    };
    var kinds = this.places.length ? ['place', 'level'] : ['level'];
    var html = '<span class="pe-3">' + str('title') + '</span> <span class="availability-group">' +
            '<label><span class="accesshide">' + str('label_kind') + '</span><select name="kind" class="custom-select ' +
            'form-select">';
    for (var k = 0; k < kinds.length; k++) {
        html += '<option value="' + kinds[k] + '">' + str('kind_' + kinds[k]) + '</option>';
    }
    html += '</select></label> <label class="oksigeniatesape-place"><span class="accesshide">' + str('label_place') +
            '</span><select name="place" class="custom-select form-select"><option value="">' +
            M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.places.length; i++) {
        // Names already escaped with format_string.
        html += '<option value="' + this.places[i].code + '">' + this.places[i].name + '</option>';
    }
    html += '</select></label> <label class="oksigeniatesape-level"><span class="pe-1">' + str('label_level') +
            '</span><input type="number" name="level" min="1" step="1" class="form-control d-inline-block" ' +
            'style="width:6em" value="2"></label></span>';
    var node = Y.Node.create('<span class="d-flex flex-wrap align-items-center">' + html + '</span>');

    // The initial value, if there is one.
    var kind = json.c === 'level' || !this.places.length ? 'level' : 'place';
    node.one('select[name=kind]').set('value', kind);
    if (json.place !== undefined && node.one('select[name=place] > option[value=' + json.place + ']')) {
        node.one('select[name=place]').set('value', json.place);
    }
    if (json.level !== undefined) {
        node.one('input[name=level]').set('value', json.level);
    }
    M.availability_oksigeniatesape.form.show(node);

    // Add event handlers (first time only).
    if (!M.availability_oksigeniatesape.form.addedEvents) {
        M.availability_oksigeniatesape.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function(e) {
            M.availability_oksigeniatesape.form.show(e.currentTarget.ancestor('.availability_oksigeniatesape'));
            M.core_availability.form.update();
        }, '.availability_oksigeniatesape select, .availability_oksigeniatesape input');
    }

    return node;
};

/**
 * Shows the place or the level, as the kind says.
 *
 * @method show
 * @param {Y.Node} node
 */
M.availability_oksigeniatesape.form.show = function(node) {
    if (!node) {
        return;
    }
    var place = node.one('select[name=kind]').get('value') === 'place';
    node.one('.oksigeniatesape-place').toggleView(place);
    node.one('.oksigeniatesape-level').toggleView(!place);
};

M.availability_oksigeniatesape.form.fillValue = function(value, node) {
    value.c = node.one('select[name=kind]').get('value');
    if (value.c === 'place') {
        value.place = node.one('select[name=place]').get('value');
    } else {
        value.level = parseInt(node.one('input[name=level]').get('value'), 10);
    }
};

M.availability_oksigeniatesape.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);
    if (value.c === 'place' && !value.place) {
        errors.push('availability_oksigeniatesape:error_place');
    }
    if (value.c === 'level' && (isNaN(value.level) || value.level < 1)) {
        errors.push('availability_oksigeniatesape:error_level');
    }
};
