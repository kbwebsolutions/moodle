M.gradingform_passfailrubriceditor = { 'templates': {}, 'eventhandler': null, 'name': null, 'Y': null };
/**
 * This function is called for each passfailrubriceditor on page.
 */
M.gradingform_passfailrubriceditor.init = function(Y, options) {
    M.gradingform_passfailrubriceditor.name = options.name;
    M.gradingform_passfailrubriceditor.Y = Y;
    M.gradingform_passfailrubriceditor.templates[options.name] = {
        'criterion': options.criteriontemplate,
        'level': options.leveltemplate
    };
    M.gradingform_passfailrubriceditor.disablealleditors();
    Y.on('click', M.gradingform_passfailrubriceditor.clickanywhere, 'body', null);
    YUI().use('event-touch', function(Y) {
        Y.one('body').on('touchstart', M.gradingform_passfailrubriceditor.clickanywhere);
        Y.one('body').on('touchend', M.gradingform_passfailrubriceditor.clickanywhere);
    });
    M.gradingform_passfailrubriceditor.addhandlers();
};

// Adds handlers for clicking submit button. This function must be called each time JS adds new elements to html.
M.gradingform_passfailrubriceditor.addhandlers = function() {
    var Y = M.gradingform_passfailrubriceditor.Y;
    var name = M.gradingform_passfailrubriceditor.name;
    if (M.gradingform_passfailrubriceditor.eventhandler) {
        M.gradingform_passfailrubriceditor.eventhandler.detach();
    }
    M.gradingform_passfailrubriceditor.eventhandler = Y.on('click', M.gradingform_passfailrubriceditor.buttonclick, '#passfailrubric-' + name + ' input[type=submit]', null);
};

// Switches all input text elements to non-edit mode.
M.gradingform_passfailrubriceditor.disablealleditors = function() {
    var Y = M.gradingform_passfailrubriceditor.Y;
    var name = M.gradingform_passfailrubriceditor.name;
    Y.all('#passfailrubric-' + name + ' .level').each(
        function(node) {
            M.gradingform_passfailrubriceditor.editmode(node, false);
        }
    );
    Y.all('#passfailrubric-' + name + ' .description').each(
        function(node) {
            M.gradingform_passfailrubriceditor.editmode(node, false);
        }
    );
};

// Function invoked on each click on the page. If level and/or criterion description is clicked
// it switches this element to edit mode. If passfailrubric button is clicked it does nothing so the 'buttonclick'
// function is invoked.
M.gradingform_passfailrubriceditor.clickanywhere = function(e) {

    if (e.type == 'touchstart') {
        return;
    }
    var el = e.target;
    // If clicked on button - disablecurrenteditor, continue.
    if (el.get('tagName') == 'INPUT' && el.get('type') == 'submit') {
        return;
    }
    // Else if clicked on level and this level is not enabled - enable it
    // or if clicked on description and this description is not enabled - enable it.
    var focustb = false;
    while (el && !(el.hasClass('level') || el.hasClass('description'))) {
        if (el.hasClass('score')) {
            focustb = true;
        }
        el = el.get('parentNode');
    }
    if (el) {
        if (el.one('textarea').hasClass('hiddenelement')) {
            M.gradingform_passfailrubriceditor.disablealleditors();
            M.gradingform_passfailrubriceditor.editmode(el, true, focustb);
        }
        return;
    }
    // Else disablecurrenteditor.
    M.gradingform_passfailrubriceditor.disablealleditors();
};

// Switch the criterion description or level to edit mode or switch back.
M.gradingform_passfailrubriceditor.editmode = function(el, editmode, focustb) {
    var ta = el.one('textarea');
    if (!editmode && ta.hasClass('hiddenelement')) {
        return;
    }
    if (editmode && !ta.hasClass('hiddenelement')) {
        return;
    }
    var pseudotablink = '<input type="text" size="1" class="pseudotablink"/>',
        taplain = ta.get('parentNode').one('.plainvalue'),
        tbplain = null,
        tb = el.one('.score input[type=text]');
    // Add 'plainvalue' next to textarea for description/definition and next to input text field for score (if applicable).
    if (!taplain) {
        ta.get('parentNode').append('<div class="plainvalue">' + pseudotablink + '<span class="textvalue">&nbsp;</span></div>');
        taplain = ta.get('parentNode').one('.plainvalue');
        taplain.one('.pseudotablink').on('focus', M.gradingform_passfailrubriceditor.clickanywhere);
        if (tb) {
            tb.get('parentNode').append('<span class="plainvalue">' + pseudotablink + '<span class="textvalue">&nbsp;</span></span>');
            tbplain = tb.get('parentNode').one('.plainvalue');
            tbplain.one('.pseudotablink').on('focus', M.gradingform_passfailrubriceditor.clickanywhere);
        }
    }
    if (tb && !tbplain) {
        tbplain = tb.get('parentNode').one('.plainvalue');
    }
    if (!editmode) {
        ta.get('parentNode').setStyle('width', '100%')

        // If we need to hide the input fields, copy their contents to plainvalue(s). If description/definition
        // is empty, display the default text ('Click to edit ...') and add/remove 'empty' CSS class to element.
        var value = ta.get('value');
        if (value.length) {
            taplain.removeClass('empty');
        } else {
            value = (el.hasClass('level')) ? M.util.get_string('levelempty', 'gradingform_passfailrubric') : M.util.get_string('criterionempty', 'gradingform_passfailrubric');
            taplain.addClass('empty');
        }
        taplain.one('.textvalue').set('innerHTML', Y.Escape.html(value));
        if (tb) {
            tbplain.one('.textvalue').set('innerHTML', Y.Escape.html(tb.get('value')));
        }
        // Hide/display textarea, textbox and plaintexts.
        taplain.removeClass('hiddenelement');
        ta.addClass('hiddenelement');
        if (tb) {
            tbplain.removeClass('hiddenelement');
            tb.addClass('hiddenelement');
        }
    } else {
        // If we need to show the input fields, set the width/height for textarea so it fills the cell.
        try {
            ta.get('parentNode').setStyle('width', '100%')
            var width = parseFloat(ta.get('parentNode').getComputedStyle('width')),
                height;
            if (el.hasClass('level')) {
                height = parseFloat(el.getComputedStyle('height')) - parseFloat(el.one('.score').getComputedStyle('height'));
            } else {
                height = parseFloat(ta.get('parentNode').getComputedStyle('height'));
            }
            ta.setStyle('width', Math.max(width - 16, 50) + 'px');
            ta.setStyle('height', Math.max(height, 20) + 'px');

        } catch (err) {
            // This browser do not support 'computedStyle', leave the default size of the textbox.
        }
        // Hide/display textarea, textbox and plaintexts.
        taplain.addClass('hiddenelement');
        ta.removeClass('hiddenelement');
        if (tb) {
            tbplain.addClass('hiddenelement');
            tb.removeClass('hiddenelement');
        }
    }
    // Focus the proper input field in edit mode.
    if (editmode) {
        if (tb && focustb) {
            tb.focus();
        } else {
            ta.focus();
        }
    }
};

// Handler for clicking on submit buttons within passfailrubriceditor element.
// Adds/deletes/rearranges criteria and/or levels on client side.
M.gradingform_passfailrubriceditor.buttonclick = function(e, confirmed) {

    var levidx;
    var parentel;
    var newcriterion;
    var newlevid;
    var newid;
    var levelsstr = '';
    var el;
    var elements_str;
    var Y = M.gradingform_passfailrubriceditor.Y;
    var name = M.gradingform_passfailrubriceditor.name;
    if (e.target.get('type') != 'submit') {
        return;
    }
    M.gradingform_passfailrubriceditor.disablealleditors();
    var chunks = e.target.get('id').split('-'),
        action = chunks[chunks.length - 1];
    if (chunks[0] != name || chunks[1] != 'criteria') {
        return;
    }
    if (chunks.length > 4 || action == 'addlevel') {
        elements_str = '#passfailrubric-' + name + ' #' + name + '-criteria-' + chunks[2] + '-levels .level';
    } else {
        elements_str = '#passfailrubric-' + name + ' .criterion';
    }
    // Prepare the id of the next inserted level or criterion.
    if (action == 'addcriterion' || action == 'addlevel' || action == 'duplicate') {
        newid = M.gradingform_passfailrubriceditor.calculatenewid('#passfailrubric-' + name + ' .criterion');
        newlevid = M.gradingform_passfailrubriceditor.calculatenewid('#passfailrubric-' + name + ' .level');
    }
    var dialog_options = {
        'scope': this,
        'callbackargs': [e, true],
        'callback': M.gradingform_passfailrubriceditor.buttonclick
    };
    if (chunks.length == 3 && action == 'addcriterion') {
        // Add new criterion.
        parentel = Y.one('#' + name + '-criteria');
        if (parentel.one('>tbody')) {
            parentel = parentel.one('>tbody');
        }

        newcriterion = M.gradingform_passfailrubriceditor.templates[name].criterion.replace(/\{LEVELS\}/, levelsstr);
        parentel.append(newcriterion.replace(/\{CRITERION-id\}/g, 'NEWID' + newid).replace(/\{.+?\}/g, ''));
        M.gradingform_passfailrubriceditor.assignclasses('#passfailrubric-' + name + ' #' + name + '-criteria-NEWID' + newid + '-levels .level');
        M.gradingform_passfailrubriceditor.addhandlers();
        M.gradingform_passfailrubriceditor.disablealleditors();
        M.gradingform_passfailrubriceditor.assignclasses(elements_str);
        M.gradingform_passfailrubriceditor.editmode(Y.one('#passfailrubric-' + name + ' #' + name + '-criteria-NEWID' + newid + '-description'), true);
    } else if (chunks.length == 5 && action == 'addlevel') {
        // Add new level.
        parentel = Y.one('#' + name + '-criteria-' + chunks[2] + '-levels');
        var newlevel = M.gradingform_passfailrubriceditor.templates[name].level.replace(/\{CRITERION-id\}/g, chunks[2]).replace(/\{LEVEL-id\}/g, 'NEWID' + newlevid).replace(/\{.+?\}/g, '');
        parentel.append(newlevel);
        M.gradingform_passfailrubriceditor.addhandlers();
        M.gradingform_passfailrubriceditor.disablealleditors();
        M.gradingform_passfailrubriceditor.assignclasses(elements_str);
        M.gradingform_passfailrubriceditor.editmode(parentel.all('.level').item(parentel.all('.level').size() - 1), true);
    } else if (chunks.length == 4 && action == 'moveup') {
        // Move criterion up.
        el = Y.one('#' + name + '-criteria-' + chunks[2]);
        if (el.previous()) {
            el.get('parentNode').insertBefore(el, el.previous());
        }
        M.gradingform_passfailrubriceditor.assignclasses(elements_str);
    } else if (chunks.length == 4 && action == 'movedown') {
        // Move criterion down.
        el = Y.one('#' + name + '-criteria-' + chunks[2]);
        if (el.next()) {
            el.get('parentNode').insertBefore(el.next(), el);
        }
        M.gradingform_passfailrubriceditor.assignclasses(elements_str);
    } else if (chunks.length == 4 && action == 'delete') {
        // Delete criterion.
        if (confirmed) {
            Y.one('#' + name + '-criteria-' + chunks[2]).remove();
            M.gradingform_passfailrubriceditor.assignclasses(elements_str);
        } else {
            dialog_options.message = M.util.get_string('confirmdeletecriterion', 'gradingform_passfailrubric');
            M.util.show_confirm_dialog(e, dialog_options);
        }
    } else if (chunks.length == 4 && action == 'duplicate') {
        // Duplicate criterion.
        var levelsdef = [];
        parentel = Y.one('#' + name + '-criteria');
        if (parentel.one('>tbody')) {
            parentel = parentel.one('>tbody');
        }
        var source = Y.one('#' + name + '-criteria-' + chunks[2]);
        if (source.all('.level')) {
            var lastcriterion = source.all('.level');
            for (levidx = 0; levidx < lastcriterion.size(); levidx++) {
                levelsdef[levidx] = lastcriterion.item(levidx).one('.definition .textvalue').get('innerHTML');
            }
        }
        for (levidx = 0; levidx < levelsdef.length; levidx++) {
            levelsstr += M.gradingform_passfailrubriceditor.templates[name].level
                .replace(/\{LEVEL-id\}/g, 'NEWID' + (newlevid + levidx))
                .replace(/\{LEVEL-definition\}/g, levelsdef[levidx]);
        }
        var description = source.one('.description .textvalue');
        newcriterion = M.gradingform_passfailrubriceditor.templates[name].criterion
            .replace(/\{LEVELS\}/, levelsstr)
            .replace(/\{CRITERION-description\}/, description.get('innerHTML'));
        parentel.append(newcriterion.replace(/\{CRITERION-id\}/g, 'NEWID' + newid).replace(/\{.+?\}/g, ''));
        M.gradingform_passfailrubriceditor.assignclasses('#passfailrubric-' + name + ' #' + name + '-criteria-NEWID' + newid + '-levels .level');
        M.gradingform_passfailrubriceditor.addhandlers();
        M.gradingform_passfailrubriceditor.disablealleditors();
        M.gradingform_passfailrubriceditor.assignclasses(elements_str);
        M.gradingform_passfailrubriceditor.editmode(Y.one('#passfailrubric-' + name + ' #' + name + '-criteria-NEWID' + newid + '-description'), true);
    } else if (chunks.length == 6 && action == 'delete') {
        // Delete level.
        if (confirmed) {
            Y.one('#' + name + '-criteria-' + chunks[2] + '-' + chunks[3] + '-' + chunks[4]).remove();
            M.gradingform_passfailrubriceditor.assignclasses(elements_str);
        } else {
            dialog_options.message = M.util.get_string('confirmdeletelevel', 'gradingform_passfailrubric');
            M.util.show_confirm_dialog(e, dialog_options);
        }
    } else {
        // Unknown action.
        return;
    }
    e.preventDefault();
};

// Properly set classes (first/last/odd/even), level width and/or criterion sortorder for elements Y.all(elements_str).
M.gradingform_passfailrubriceditor.assignclasses = function(elements_str) {
    var elements = M.gradingform_passfailrubriceditor.Y.all(elements_str);
    for (var i = 0; i < elements.size(); i++) {
        elements.item(i).removeClass('first').removeClass('last').removeClass('even').removeClass('odd').
        addClass(((i % 2) ? 'odd' : 'even') + ((i === 0) ? ' first' : '') + ((i == elements.size() - 1) ? ' last' : ''));
        elements.item(i).all('input[type=hidden]').each(
            /*jshint loopfunc: true */
            function(node) {
                if (node.get('name').match(/sortorder/)) {
                    node.set('value', i);
                }
            }
        );
        if (elements.item(i).hasClass('level')) {
            elements.item(i).set('width', Math.round(100 / elements.size()) + '%');
        }
    }
};

// Returns unique id for the next added element, it should not be equal to any of Y.all(elements_str) ids.
M.gradingform_passfailrubriceditor.calculatenewid = function(elements_str) {
    var newid = 1;
    M.gradingform_passfailrubriceditor.Y.all(elements_str).each(function(node) {
        var idchunks = node.get('id').split('-'),
            id = idchunks.pop();
        if (id.match(/^NEWID(\d+)$/)) {
            newid = Math.max(newid, parseInt(id.substring(5)) + 1);
        }
    });
    return newid;
};