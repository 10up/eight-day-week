/**
 * Eight Day Week
 * http://10up.com
 *
 * Copyright (c) 2015 10up, Josh Levinson, Brent Schultz
 * Licensed under the GPLv2+ license.
 */
/* global EDW_Vars */
(function (window, $) {
	'use strict';

	var PP_Ajax = {
		_data: {
			'_ajax_nonce': EDW_Vars.nonce,
		},

		_send: wp.ajax.send,
		_post: wp.ajax.post,

		post: function (action, data, $input, button_selector) {
			//merge the object default data
			$.extend( data, this._data );

			var promise = this._post(action, data);

			this.locker(promise, $input, button_selector);

			//assign a new nonce
			this.suhcurity( promise );

			return promise;
		},

		locker: function (promise, $input, button_selector) {
			PP_Utils.lock_input($input, button_selector);
			promise.always(function () {
				PP_Utils.release_input($input, button_selector);
			});
		},

		suhcurity: function( promise ) {
			var ppa = this;
			promise.always(function (data) {
				if( typeof data === 'object' && typeof data._ajax_nonce !== 'undefined' ) {
					ppa.set_nonce( data._ajax_nonce );
				} else {
					PP_Utils.flash_error( 'Whoops! Looks like this page has been sitting for a while. Try refreshing!' );
				}
			});
		},

		set_nonce: function (new_nonce) {
			this._data._ajax_nonce = new_nonce;
		},

		send_get: function (action, data, $input, button_selector) {

			//merge the object default data
			$.extend( data, this._data );

			var options = {
				type: 'GET',
				data: data,
			};
			var promise = this._send(action, options);
			this.locker(promise, $input, button_selector);

			//assign a new nonce
			this.suhcurity( promise );

			return promise;
		}
	};

	var PP_Utils;
	PP_Utils = {
		
		$error_msg: $('#pi-section-error'),

		//this is cached + used externally in other objects
		$metabox_wrap: $('.meta-box-sortables'),

		//primarily used for hijacking enter keypress
		$postbox_wrap: $('#postbox-container-2'),

		add_progress_indicator: function ($after) {
			$after.after(EDW_Vars.progress_img);
		},

		remove_progress_indicator: function () {
			$('.edw-loading').remove();
		},

		handlers: function () {
			this.$postbox_wrap.on('keypress', 'input[type="text"]', this.submit_to_buttons);
		},

		get_inputs: function( $input, button ) {
			var $button;

			var inputs = {
				button: false,
				input: false,
			};

			if (typeof button === 'undefined' && typeof $input !== 'undefined' ) {
				$button = $input.siblings('.button');
			} else if (!( button instanceof $ )) {
				$button = button;
			} else {
				$button = $(button);
			}

			if ( typeof $button !== 'undefined' && $button.length) {
				inputs.button = $button;
			}

			if ( typeof $input !== 'undefined' && $input.length ) {
				inputs.input = $input;
			}

			return inputs;

		},

		lock_input: function ($input, button) {

			var inputs = this.get_inputs( $input, button );

			if ( inputs.button ) {
				inputs.button.hide();
			}

			if ( inputs.input ) {
				inputs.input.attr('disabled', 'disabled');
				inputs.input.addClass('locked');
				PP_Utils.add_progress_indicator( inputs.input );
			}

			if( ! inputs.input && inputs.button ) {
				PP_Utils.add_progress_indicator( inputs.button );
			}

		},

		release_input: function ($input, button) {

			var inputs = this.get_inputs( $input, button );

			if ( inputs.input ) {
				PP_Utils.remove_progress_indicator( inputs.input );
				inputs.input.removeAttr('disabled');
				inputs.input.removeClass('locked');
			}

			if( ! inputs.input && inputs.button ) {
				PP_Utils.remove_progress_indicator( inputs.button );
			}

			if ( inputs.button ) {
				inputs.button.show();
			}
		},

		/**
		 * Redirect default submit behavior of inputs within this context
		 *
		 * By default, if one presses the enter key
		 * while within an input on the post editor,
		 * the entire page will submit (update).
		 * This is not desired behavior for this plugin.
		 * Instead, this handler stops the keypress
		 * And maps it to a mocked click on an adjacent button,
		 * if one exists in the DOM.
		 *
		 * @param e
		 * @returns {boolean}
		 */
		submit_to_buttons: function (e) {
			if (13 === e.which) {
				e.preventDefault();
				var $button = $(this).siblings('button');
				if ($button.length) {
					$button.click();
				}
				return false;
			}
		},

		flash_error: function (error_msg) {
			this.$error_msg.text(error_msg);
			this.$error_msg.show().delay(4500).fadeOut();
		},
		
	};
	PP_Utils.handlers();

	var Section_Manager;
	Section_Manager = {

		//cash
		$section_ids: $('#pi-section-ids'),
		$titles: $('[id^="pi-sections-box"].postbox .hndle span'),
		$pi_section_name: $('#pi-section-name'),
		print_issue_id: $('#post_ID').val(),

		init: function () {
			var sm = this;
			this.handlers();
			$(document).ready(function () {
				var call = $.proxy(sm.ready, sm);
				call();
			});
		},

		ready: function () {
			//this only needs to be fired once since new section metaboxes are made from existing ones
			this.$titles.attr('contenteditable', 'true');
			this.$titles.attr('title', 'Click to edit section name');

			//sortable hijacks clicks, this "cancels" that
			//so contenteditable works
			PP_Utils.$metabox_wrap.sortable({
				cancel: ':input,button,[contenteditable]'
			});

			//prevent toggling from being bound to the h3
			//so the contenteditable doesn't trigger toggle
			$('[id^="pi-sections-box"].postbox .hndle').unbind();

			//add titles to data attribute of title span
			this.set_initial_title_data();
		},

		//add event handlers
		handlers: function () {
			var sm = this;

			$('#pi-section-add').on('click', function (e) {
				e.preventDefault();
				var call = $.proxy(sm.toggle_section_add_inputs, sm);
				call();
			});

			$('#pi-section-add-confirm').on('click', function (e) {
				e.preventDefault();
				var call = $.proxy(sm.add_section, sm);
				call();
			});

			PP_Utils.$metabox_wrap.on('click', '.postbox.js .handlediv', this.toggle_handler);

			PP_Utils.$metabox_wrap.on('blur', '.postbox .hndle span', function () {
				var call = $.proxy(sm.check_title_change, sm, $(this));
				call();
			});

			PP_Utils.$metabox_wrap.on('keypress', '.postbox .hndle span', sm.map_enter_to_blur);

			PP_Utils.$metabox_wrap.on('click', '.pi-section-delete a', function (e) {
				e.preventDefault();
				var call = $.proxy(sm.delete_section, sm, $(this));
				call();
			});

		},

		destroy_sortable: function() {
			PP_Utils.$metabox_wrap.sortable('destroy');
		},

		set_initial_title_data: function () {
			this.$titles.each(this.set_title_data);
		},

		check_title_change: function ($el) {
			var existing_title = this.get_existing_title($el);
			var new_title = this.get_new_title($el);
			if (existing_title !== new_title) {
				this.title_changed($el, new_title);
			}
		},

		/**
		 * Make an enter keypress in a section name
		 * Behave as though it were a "tab" out (blur/focusout)
		 *
		 * @param event The keypress event
		 * @param $title The title in which the keypress occurred
		 */
		map_enter_to_blur: function(event) {
			if( 13 === event.which) {
				event.preventDefault();
				event.stopImmediatePropagation();
				$(this).blur();
			}
		},

		title_changed: function ($el, title) {
			var sm = this;
			// fire off server call
			var post_id = $el.parents('.postbox').find('.section_id').val();
			var promise = this.save_section_title(title, post_id, $el);

			//lock title while server call is being made
			this.set_title_lock($el);

			promise.fail(function (data) {
				var call = $.proxy(sm.reset_title, sm, $el);
				call();
				PP_Utils.flash_error(data.message);
			});
			promise.done(function (data) {
				// update data attr
				var call = $.proxy(sm.set_title_data, sm, $el);
				call();
			});
			promise.always(function () {
				var call = $.proxy(sm.release_title_lock, sm, $el);
				call();
			});
		},

		release_title_lock: function ($el) {
			$el.attr('contenteditable', true);
		},

		set_title_lock: function ($el) {
			$el.attr('contenteditable', false);
		},

		reset_title: function ($el) {
			$el.text($el.data('title'));
		},

		save_section_title: function (title, post_id, $input, button) {
			return PP_Ajax.post('pp-update-section-title', {
				title: title,
				post_id: post_id,
			}, $input, button);
		},

		get_new_title: function ($el) {
			return $el[0].textContent;
		},

		get_existing_title: function ($el) {
			return $el.data('title');
		},

		set_title_data: function () {
			$(this).data('title', $(this)[0].textContent);
		},

		delete_section: function ($el) {
			if (!window.confirm('Are you sure you want to delete this section?')) {
				return;
			}
			var $section = $el.parents('.postbox');
			var section_id = $section.find('.section_id').val();
			var section_ids = this.$section_ids.val().split(',');
			var index = section_ids.indexOf(section_id);
			if (index > -1) {
				section_ids.splice(index, 1);
				this.$section_ids.val(section_ids.join(','));
			}
			$section.remove();
		},

		//section creating stuffs
		add_section: function () {
			var sm = this;
			var name = this.$pi_section_name.val();
			if (!name.length) {
				return this.flash_error('Please enter a section name.');
			}

			var section_promise = sm.create_section(name, this.$pi_section_name);

			section_promise.fail(function (data) {
				PP_Utils.flash_error(data.message);
			});

			section_promise.done(function (data) {
				var $new_section = sm.$insert_section(name, data.section_id);
				sm.clear_add_input();
				sm.toggle_section_add_inputs();
				sm.save_metabox_order();
				PP_Utils.$postbox_wrap.trigger('pp-section-added', $new_section, data.section_id);
			});
		},

		/**
		 * Save the metabox order to the server
		 * for js-appended metaboxes
		 * Otherwise, they get thrown at the top on update
		 */
		save_metabox_order: function () {
			if (typeof postboxes !== 'undefined') {
				postboxes.save_order('print-issue');
			}
		},

		create_section: function (name, $input, button) {
			var that = this;
			return PP_Ajax.post('pp-create-section', {
				name: name,
				print_issue_id: that.print_issue_id,
			}, $input, button);
		},

		$insert_section: function (name, id) {
			var new_section = $('[id^="pi-sections-template"].postbox').first().clone();
			new_section.attr('id', 'pi-sections-box-' + id);
			if (new_section.find('.hndle > span').length > 0) {
				new_section.find('.hndle > span').text(name).attr('contenteditable', 'true');
			} else {
				new_section.find('.hndle').text(name);
			}
			new_section.addClass('js');
			new_section.removeClass('hide-if-js');
			new_section.find('.section_id').val(id);

			//set article ids hidden input name
			new_section.find('.pi-article-ids').attr('name', 'pi-article-ids[' + id + ']');

			$('#normal-sortables').append(new_section);
			this.append_section_id(id);
			return new_section;
		},

		append_section_id: function (id) {
			var current = this.$section_ids.val();
			if (current.length) {
				current += ',';
			}
			current += id;
			this.$section_ids.val(current);
		},
		//end section creating stuffs

		//visual stuffs

		/**
		 * Handle toggles on js-appended metaboxes
		 */
		toggle_handler: function () {
			if (typeof postboxes !== 'undefined') {
				var p = $(this).parent('.postbox'), id = p.attr('id');

				p.toggleClass('closed');

				postboxes.save_state('print-issue');

				if (id) {
					if (!p.hasClass('closed') && $.isFunction(postboxes.pbshow))
						postboxes.pbshow(id);
					else if (p.hasClass('closed') && $.isFunction(postboxes.pbhide))
						postboxes.pbhide(id);
				}

				$(document).trigger('postbox-toggled', p);
			}
		},
		

		toggle_section_add_inputs: function () {
			this.toggle_add_info();
			this.toggle_add_section();
		},

		toggle_add_info: function () {
			$('#pi-section-add-info').toggle();
		},

		toggle_add_section: function () {
			$('#pi-section-add').toggle();
		},

		clear_add_input: function () {
			$('#pi-section-name').val('');
		},
	};

	Section_Manager.init();

	var Article_Manager;
	Article_Manager = {

		sortable_container: '#postbox-container-2',
		$bulk_article_status_select: $('#bulk-action-selector-top'),
		article_search_cache: {},

		init: function () {
			var am = this;
			this.handlers();
			$(document).ready(function () {
				var call = $.proxy(am.ready, am);
				call();
			});
		},

		handlers: function () {
			var am = this;
			PP_Utils.$metabox_wrap.on('click', '.pi-article-add', function (e) {
				e.preventDefault();
				var call = $.proxy(am.toggle_article_add_inputs, am, $(this));
				call();
			});
			PP_Utils.$metabox_wrap.on('click', '.pi-article-search', function (e) {
				e.preventDefault();
				var call = $.proxy(am.search_for_articles, am, $(this));
				call();
			});
			PP_Utils.$metabox_wrap.on('click', '.pi-article-result-confirm', function (e) {
				e.preventDefault();
				var call = $.proxy(am.add_article, am, $(this));
				call();
			});

			PP_Utils.$metabox_wrap.on('click', '.pi-article-remove', function (e) {
				e.preventDefault();
				var call = $.proxy(am.remove_article, am, $(this));
				call();
			});

			$('#bulk-edit-article-status-submit').click(function (e) {
				e.preventDefault();
				var call = $.proxy(am.edit_checked_article_status, am, $(this));
				call();
			});
			$('#bulk-edit-article-status-apply-all').click(function (e) {
				e.preventDefault();
				var call = $.proxy(am.edit_all_article_status, am, $(this));
				call();
			});

			PP_Utils.$postbox_wrap.on('pp-section-added', function(e, $new_section){
				var call = $.proxy(am.refresh_autocomplete, am, $new_section);
				call();
			});

		},

		ready: function () {
			this.init_sortable();
			this.init_autocomplete();
		},

		init_autocomplete: function() {
			var am = this;
			$('.pi-article-title').autocomplete({
				source: function (request, response) {
					var ac = this;
					var term = request.term;
					if (term in am.article_search_cache) {
						response(am.article_search_cache[term]);
						return;
					}
					var articles_promise = am.get_articles(term);
					articles_promise.done(function (data) {
						var articles = $.makeArray(data.articles);
						am.article_search_cache[term] = articles;
						response(articles);
					});
					articles_promise.fail(function (data) {
						response([]);
					});
				},
				select: function(event, ui){
					event.preventDefault();
					am.add_article($(this), ui.item.value);
					$(this).autocomplete('close');
					$(this).val('');
				}
			});
		},

		refresh_autocomplete: function( $new_section ) {
			$('[id^="pi-sections-box"].postbox').not($new_section).children('.pi-article-title').autocomplete('destroy');
			this.init_autocomplete();
		},

		init_sortable: function () {
			var am = this;
			var $container = $(this.sortable_container);
			if (( typeof $container.sortable('instance') === 'undefined' ) &&
				$container.length) {
				$container.sortable({
					placeholder: 'sortable-placeholder',
					items: '.postbox[id^="pi-sections-box"] tr',
					containment: 'parent',
					cursor: 'move',
					tolerance: 'pointer',
					forcePlaceholderSize: true,
					helper: 'clone',
					opacity: 0.65,
					update: function (event, ui) {
						var call = $.proxy(am.update_sortable, am, event, ui);
						call();
					},
				});
			} else if( $container.length ) {
				$container.sortable( 'refresh' );
			}
		},

		update_sortable: function (event, ui) {
			var $article = ui.item;
			var $section = $article.parents('.postbox');
			var sorted_ids = $article.siblings('tr').addBack().map(function () {
				return $(this).data('article-id');
			}).get();
			this.set_article_ids($section, sorted_ids);
		},

		refresh_sortable: function ($section) {
			var $container = $(this.sortable_container);
			if( typeof $container.sortable('instance') !== 'undefined' ) {
				this.destroy_sortable();
			}
			this.init_sortable();
		},

		destroy_sortable: function () {
			$(this.sortable_container).sortable('destroy');
		},

		set_article_ids: function ($section, ids, $input) {
			var $article_ids = $input || $section.find('.pi-article-ids');
			$article_ids.val(ids.join(','));
		},

		flash_error: function ($section, error_msg) {
			$section.find('.pi-error-msg').text(error_msg);
			$section.find('.pi-error-msg').show().delay(4500).fadeOut();
		},

		toggle_article_add_inputs: function ($clicked_button) {
			var $section = $clicked_button.parents('.postbox');
			var $articles_wrap = $clicked_button.siblings('.pi-articles');
			var $article_add_info = $clicked_button.siblings('.pi-article-add-info');
			$clicked_button.toggle();
			$article_add_info.toggle();
		},

		get_articles: function (title, $input, button) {
			return PP_Ajax.send_get('pp-get-articles', {
				title: title,
			}, $input, button);
		},

		add_article: function ($input, article_id) {
			var am = this;
			var $section = $input.parents('.postbox');

			if (!article_id) {
				return;
			}

			if ( this.article_exists_in_section( article_id, '', $section ) ) {
				am.flash_error($section, 'Whoops! You\'ve already added that article to this section.');
				return;
			}

			var article_row = this.get_article_row(article_id, $input);

			article_row.done(function (data) {
				am.$insert_article($section, data);
				am.add_article_id($section, article_id);
			});
			article_row.fail(function (data) {
				am.flash_error($section, data.message);
			});
		},

		$insert_article: function ($section, article_data) {

			var $articles_table = $section.find('.wp-list-table tbody');
			var $new_article = $(article_data.html);

			//insert new article
			$articles_table.append($new_article);

			//kick sortable in the pants
			this.refresh_sortable($section);
		},

		edit_article_meta: function( $article, article_info ) {
			for ( var index in article_info ) {
				var $el = $article.children( 'td.column-' + index );
				if( ! $el.length ) {
					continue;
				}
				if( this.is_html( article_info[ index ] ) ) {
					$el.html( article_info[ index ] );
				} else {
					$el.text( article_info[ index ] );
				}
			}
		},

		is_html: function( string ) {
			var htmlized = $.parseHTML( string );
			var is_html =  false;
			for( var index in htmlized ) {
				if ( '#text' !== htmlized[index].nodeName ) {
					is_html = true;
					break;
				}
			}
			return is_html;
		},

		add_article_id: function ($section, article_id) {
			var $input = $section.find('.pi-article-ids');
			var current = this.get_article_ids( $input, $section ) || [];
			if(current.indexOf(article_id) > -1 ){
				return;
			}
			current.push(parseInt(article_id));
			this.set_article_ids($section, current, $input);
		},

		get_index_of_article_id: function( article_id, $input, $section ) {
			var current = this.get_article_ids( $input, $section ) || [];
			return current.indexOf(parseInt(article_id));
		},

		article_exists_in_section: function( article_id, $input, $section ) {
			return this.get_index_of_article_id( article_id, $input, $section ) > -1;
		},

		remove_article_id: function ($section, article_id) {
			var $input = $section.find('.pi-article-ids');
			var current = this.get_article_ids($input);
			if (!current) {
				return;
			}
			var index = current.indexOf(parseInt(article_id));
			if (index > -1) {
				current.splice(index, 1);
				this.set_article_ids($section, current, $input);
				return true;
			}
			return false;
		},

		/**
		 * Get Article IDs from hidden input within a section
		 * Or, optionally from an explicit input
		 *
		 * @param $section $ The containing section
		 * @param $input $ The input to grab IDs from
		 * (optional, will default to finding from section)
		 *
		 * @returns array A Article IDs (integers!)
		 */
		get_article_ids: function( $input, $section ) {
			if(!$input.length && typeof $section !== 'undefined' && $section.length) {
				$input = $section.find('.pi-article-ids');
			}
			var current = $input.val();
			if (!current.length) {
				return false;
			}
			current = current.split(',');
			return current.map(function (x) {
				return parseInt(x, 10);
			});
		},

		remove_article: function ($remove_link) {
			var $section = $remove_link.parents('.postbox');
			var $row = $remove_link.parents('tr');
			var article_id = $remove_link.data('article-id');
			$row.remove();
			this.refresh_sortable($section);
			this.remove_article_id($section, article_id);
		},

		get_article_row: function (article_id, $input, button_selector) {
			return PP_Ajax.send_get('pp-get-article-row', {
				article_id: article_id,
			}, $input, button_selector);
		},

		edit_checked_article_status: function ($apply_button) {
			var $checked = $('input.article-status:checked');
			this.edit_supplied_article_status($checked);
		},

		edit_all_article_status: function( $apply_button ) {
			if (window.confirm('Are you sure you want to apply this status to all articles in this issue?')) {
				//grab all checkboxes except templates
				var $checked = $('tr[data-article-id!="0"] input.article-status');
				this.edit_supplied_article_status( $checked );
			}
		},

		edit_supplied_article_status: function ($checked) {
			var am = this;
			var status_id = this.$bulk_article_status_select.val();
			var status = this.$bulk_article_status_select.children('option:selected').text();
			var ids = $checked.map(function () {
				return this.value;
			}).get();

			if (!ids.length) {
				PP_Utils.flash_error( 'Check some article boxes first!' );
				return;
			}

			ids = ids.join(',');
			var promise = this.edit_article_status(status_id, ids, this.$bulk_article_status_select);

			promise.done(function () {
				am.add_new_article_statuses(status, $checked);
			});

			promise.fail(function ( data ) {
				PP_Utils.flash_error( data.message );
			});
		},

		edit_article_status: function (status, checked_ids, $input, $button_selector) {
			return PP_Ajax.post('pp-bulk-edit-article-statuses', {
				status: status,
				checked_articles: checked_ids,
			}, $input, $button_selector);
		},

		/**
		 * Add article status text to the list table
		 *
		 * @param status
		 * @param $checked_inputs
		 */
		add_new_article_statuses: function (status, $checked_inputs) {
			var am = this;
			$checked_inputs.each(function () {
				am.edit_article_meta( $(this).parents( 'tr'), {
					article_status: status,
				} );
			});
		},
	};
	Article_Manager.init();

	var PP_DOM_Stuff = {
		init: function(){
			this.handlers();
		},
		handlers: function() {
			$(document).ready( this.ready );
			$(document).ready( this.rov_view );
		},
		ready: function() {
			var $submitdiv = $('#submitdiv');
			$submitdiv.children('.handlediv').hide();
			$submitdiv.children('.hndle').off( 'click.postboxes' ).children('span').text('Issue Date');
			var $mpa = $('#major-publishing-actions');
			$mpa.wrapInner('<div id="publishing-actions"></div>');
			$mpa.prepend('<h3 class="hndle">Actions</h3>');
		},

		rov_view: function() {
			if( EDW_Vars.rov || ! EDW_Vars.cuc_edit_print_issue ) {
				Article_Manager.destroy_sortable();
				Section_Manager.destroy_sortable();
				$('#title').attr('disabled','disabled');
			}
		},
	};
	PP_DOM_Stuff.init();

	var Article_Export_Manager;
	Article_Export_Manager = {
		$export_checked_button: $('#article-export-checked'),
		$export_all_button: $('#article-export-all'),

		init: function(){
			this.handlers();
		},

		handlers: function(){

			var aem = this;

			this.$export_checked_button.click(function(event){
				var call = $.proxy(aem.handle_article_export_checked, aem, $(this), event);
				call();
			});

			this.$export_all_button.click(function(event){
				var call = $.proxy(aem.handle_article_export_all, aem, $(this), event);
				call();
			});

		},

		handle_article_export_checked: function( $button, event ){
			event.preventDefault();
			var $checked = $('input.article-status:checked');
			this.export_supplied_articles($checked, $button);
		},

		handle_article_export_all: function( $button, event ){
			event.preventDefault();
			var $checked = $('tr[data-article-id!="0"] input.article-status');
			this.export_supplied_articles($checked, $button);
		},

		update_export_meta: function( ids, $button ) {
			return PP_Ajax.post('pp-article-export-update', {
				article_ids: ids,
			}, undefined, $button );
		},

		$update_export_meta: function( $checked, article_info ) {
			var am = Article_Manager;
			var $articles = $checked.map(function(){
				return $(this).parents('tr');
			});
			$articles.each(function ( i, $article ) {
				am.edit_article_meta( $article, article_info );
			});
		},

		export_supplied_articles: function( $checked, $button ){
			var aem = this;

			var ids = $checked.map(function () {
				return this.value;
			}).get();

			if (!ids.length) {
				PP_Utils.flash_error( 'Check some article boxes first!' );
				return;
			}

			ids = ids.join(',');

			var update_promise = this.update_export_meta( ids, $button );
			update_promise.done(function( data ){
				aem.$update_export_meta( $checked, data );
			});

			var $form = $('<form />', {
				action: wp.ajax.settings.url,
				method: 'post',
			});

			$form.append($('<input />',{
				type: 'hidden',
				name: 'article_ids',
				value: ids,
			}));

			$form.append($('<input />',{
				type: 'hidden',
				name: 'print_issue_id',
				value: $('#post_ID').val(),
			}));

			$form.append($('<input />',{
				type: 'hidden',
				name: 'print_issue_title',
				value: $('#title').val(),
			}));

			$form.append($('<input />',{
				type: 'hidden',
				name: '_ajax_nonce',
				value: EDW_Vars.nonce
			}));

			$form.append($('<input />',{
				name: 'action',
				type: 'hidden',
				value: 'pp-article-export',
			}));

			$('body').append($form);
			$form.submit();
			$form.remove();
		},

	};
	Article_Export_Manager.init();


})(this, jQuery);
