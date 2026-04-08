/**
 * Section-aware canvas drop for Everest Forms builder (jQuery).
 * Drops from the field palette can target intent derived from pointer position.
 *
 * @package EverestForms
 */
/* global evf_data, jQuery */
(function ($, window) {
	'use strict';

	var EDGE_X = 0.22;
	var EDGE_Y_TOP = 0.28;
	var EDGE_Y_BOTTOM = 0.28;
	/** Min half-height of the “insert row” band between two rows (px). */
	var ROW_GAP_PX = 36;

	/**
	 * @typedef {Object} EVFCanvasSectionRef
	 * @property {jQuery} $el - Root DOM node (multipart part panel or field wrapper).
	 * @property {string} id - data-evf-section-id, part id, or "main".
	 */

	/**
	 * @typedef {Object} EVFDropIntent
	 * @property {string} type - Intent kind.
	 * @property {EVFCanvasSectionRef} [section]
	 * @property {jQuery} [$row]
	 * @property {jQuery} [$grid]
	 * @property {jQuery} [$field]
	 * @property {string} [mode] - before|after for horizontal split.
	 * @property {string} [sibling] - before|after for same-column insert.
	 * @property {jQuery} [$anchorRow] - Row after which to insert (newRow).
	 */

	function canvasDropEnabled() {
		return (
			typeof evf_data !== 'undefined' &&
			evf_data.enable_canvas_drop &&
			String(evf_data.enable_canvas_drop) === '1'
		);
	}

	/**
	 * Sections: visible multipart part, else single wrapper.
	 * @returns {jQuery}
	 */
	function getSectionRoots() {
		var $parts = $('.everest-forms-field-wrap .everest-forms-part:visible');
		if ($parts.length) {
			return $parts;
		}
		return $('.everest-forms-field-wrap .evf-admin-field-wrapper').first();
	}

	function isRepeaterContext($node) {
		return (
			$node.closest('.evf-repeater-fields').length ||
			$node.closest('.everest-forms-field-repeater-fields').length ||
			$node.closest('.evf-repeatable-grid').length
		);
	}

	function sectionRefFrom$($root) {
		var id =
			$root.attr('data-part-id') ||
			$root.attr('data-evf-section-id') ||
			$root.attr('id') ||
			'main';
		return { $el: $root, id: String(id) };
	}

	function isExistingFieldDragging() {
		return $('.everest-forms-field.ui-sortable-helper').length > 0;
	}

	function pickFieldUnderPoint(clientX, clientY) {
		var stack = document.elementsFromPoint(clientX, clientY);
		var i,
			el,
			$f;
		for (i = 0; i < stack.length; i++) {
			el = stack[i];
			if ($(el).closest('.ui-draggable-dragging').length) {
				continue;
			}
			if ($(el).closest('.ui-sortable-helper').length) {
				continue;
			}
			$f = $(el).closest('.everest-forms-field');
			if (
				$f.length &&
				$f.attr('data-field-type') !== 'repeater-fields' &&
				!isRepeaterContext($f)
			) {
				return $f;
			}
		}
		return $();
	}

	function pickGridUnderPoint(clientX, clientY, $section) {
		var stack = document.elementsFromPoint(clientX, clientY);
		var i,
			el,
			$g;
		for (i = 0; i < stack.length; i++) {
			el = stack[i];
			$g = $(el).closest('.evf-admin-grid');
			if (
				$g.length &&
				$section.find($g).length &&
				!$g.hasClass('evf-repeatable-grid') &&
				!isRepeaterContext($g)
			) {
				return $g;
			}
		}
		return $();
	}

	/**
	 * Hit-test row gap between two rows (horizontal bar intent).
	 */
	function pickRowGapIntent(clientX, clientY, $section) {
		var $rows = $section.find('.evf-admin-row');
		if ($rows.length < 2) {
			return null;
		}
		var y = clientY,
			x = clientX,
			idx,
			r0,
			r1,
			b0,
			t1,
			mid,
			tol,
			gapSize,
			minL,
			maxR;
		for (idx = 0; idx < $rows.length - 1; idx++) {
			r0 = $rows.eq(idx)[0].getBoundingClientRect();
			r1 = $rows.eq(idx + 1)[0].getBoundingClientRect();
			b0 = r0.bottom;
			t1 = r1.top;
			gapSize = Math.max(0, t1 - b0);
			mid = (b0 + t1) / 2;
			tol = Math.max(ROW_GAP_PX, gapSize / 2 + 24);
			minL = Math.min(r0.left, r1.left) - 8;
			maxR = Math.max(r0.right, r1.right) + 8;
			if (x < minL || x > maxR) {
				continue;
			}
			if (Math.abs(y - mid) <= tol) {
				return { $anchorRow: $rows.eq(idx), $nextRow: $rows.eq(idx + 1) };
			}
		}
		return null;
	}

	/**
	 * Hit-test “before first row” / “after last row” zones.
	 * This fixes the UX gap when there is only one row (no between-row gap),
	 * and provides a reliable area to add a new row at the top/bottom.
	 */
	function pickRowEdgeIntent(clientX, clientY, $section, includeInsideBand) {
		var $rows = $section.find('.evf-admin-row');
		if ($rows.length < 1) {
			return null;
		}

		var x = clientX;
		var y = clientY;

		var first = $rows.first()[0].getBoundingClientRect();
		var last = $rows.last()[0].getBoundingClientRect();

		var minL = Math.min(first.left, last.left) - 8;
		var maxR = Math.max(first.right, last.right) + 8;
		if (x < minL || x > maxR) {
			return null;
		}

		// Above first row.
		// For new-field drags, allow an easier band that also includes the top area inside row.
		if (
			(includeInsideBand &&
				y >= first.top - ROW_GAP_PX &&
				y <= first.top + ROW_GAP_PX) ||
			(!includeInsideBand && y >= first.top - ROW_GAP_PX && y < first.top)
		) {
			return { position: 'before', $nextRow: $rows.first() };
		}

		// Below last row.
		if (
			(includeInsideBand &&
				y >= last.bottom - ROW_GAP_PX &&
				y <= last.bottom + ROW_GAP_PX) ||
			(!includeInsideBand && y > last.bottom && y <= last.bottom + ROW_GAP_PX)
		) {
			return { position: 'after', $anchorRow: $rows.last() };
		}

		return null;
	}

	/**
	 * Grids in row (inside .evf-grid-lists when present).
	 */
	function getRowGrids($row) {
		var $g = $row.find('.evf-grid-lists > .evf-admin-grid');
		if ($g.length) {
			return $g;
		}
		return $row.find('.evf-admin-grid').not('.evf-repeatable-grid');
	}

	/**
	 * Insert a placeholder element into a grid at the nearest vertical position.
	 * Used to keep “before/after” style drops feeling consistent across columns.
	 */
	function insertIntoGridAtY($grid, clientY, $el) {
		var $fields = $grid.children('.everest-forms-field');
		if (!$fields.length) {
			$grid.append($el);
			return;
		}
		var i, r, mid;
		for (i = 0; i < $fields.length; i++) {
			r = $fields.get(i).getBoundingClientRect();
			mid = r.top + r.height / 2;
			if (clientY < mid) {
				$fields.eq(i).before($el);
				return;
			}
		}
		$grid.append($el);
	}

	function snapshotRowColumns($row) {
		var $grids = getRowGrids($row);
		var cols = [];
		$grids.each(function () {
			var ids = [];
			$(this)
				.children('.everest-forms-field')
				.each(function () {
					ids.push($(this).attr('data-field-id'));
				});
			cols.push(ids);
		});
		return cols;
	}

	function restoreRowColumns($row, cols) {
		var $grids = getRowGrids($row);
		var gi, fi, id, $el;
		for (gi = 0; gi < $grids.length; gi++) {
			for (fi = 0; fi < (cols[gi] || []).length; fi++) {
				id = cols[gi][fi];
				$el = $('#everest-forms-field-' + id);
				if ($el.length) {
					$grids.eq(gi).append($el);
				}
			}
		}
	}

	function expandRowColumns(builder, $row, side) {
		if (!builder || !builder.setRowGridCount) {
			return null;
		}
		var prevCols = snapshotRowColumns($row);
		var prevCount = prevCols.length || 1;
		var nextCount = Math.min(4, prevCount + 1);
		if (nextCount === prevCount) {
			return null;
		}

		// Build new column model, optionally shifting right when adding on the left.
		var nextCols = [];
		var i;
		if (side === 'left') {
			nextCols.push([]); // new empty first column
			for (i = 0; i < prevCols.length; i++) {
				nextCols.push(prevCols[i]);
			}
		} else {
			for (i = 0; i < prevCols.length; i++) {
				nextCols.push(prevCols[i]);
			}
			nextCols.push([]); // new empty last column
		}

		builder.setRowGridCount($row, nextCount);
		restoreRowColumns($row, nextCols);
		builder.bindFields();
		return getRowGrids($row);
	}

	/**
	 * @param {number} clientX
	 * @param {number} clientY
	 * @returns {EVFDropIntent|null}
	 */
	function computeDropIntent(clientX, clientY) {
		var $sections = getSectionRoots();
		if (!$sections.length) {
			return null;
		}

		var s,
			$sec,
			secRef,
			rect,
			gap,
			$field,
			fr,
			rx,
			ry,
			$row,
			$grid,
			colCount;

		var existingDrag = isExistingFieldDragging();
		for (s = 0; s < $sections.length; s++) {
			$sec = $sections.eq(s);
			secRef = sectionRefFrom$($sec);
			rect = $sec[0].getBoundingClientRect();
			if (
				clientX < rect.left ||
				clientX > rect.right ||
				clientY < rect.top ||
				clientY > rect.bottom
			) {
				continue;
			}

			if (!$sec.find('.evf-admin-row').length) {
				return { type: 'emptySection', section: secRef, $section: $sec };
			}

			var edge = pickRowEdgeIntent(clientX, clientY, $sec, !existingDrag);
			if (edge) {
				return {
					type: 'newRow',
					position: edge.position,
					section: secRef,
					$section: $sec,
					$anchorRow: edge.$anchorRow,
					$nextRow: edge.$nextRow,
				};
			}

			gap = pickRowGapIntent(clientX, clientY, $sec);
			if (gap) {
				return {
					type: 'newRow',
					position: 'between',
					clientY: clientY,
					section: secRef,
					$section: $sec,
					$anchorRow: gap.$anchorRow,
					$nextRow: gap.$nextRow,
				};
			}

			$field = pickFieldUnderPoint(clientX, clientY);
			if ($field.length && $sec.find($field).length) {
				$row = $field.closest('.evf-admin-row');
				$grid = $field.closest('.evf-admin-grid');
				if (isRepeaterContext($field)) {
					return null;
				}
				edge = pickRowEdgeIntent(clientX, clientY, $sec, !existingDrag);
				if (edge) {
					return {
						type: 'newRow',
						position: edge.position,
						section: secRef,
						$section: $sec,
						$anchorRow: edge.$anchorRow,
						$nextRow: edge.$nextRow,
					};
				}
				gap = pickRowGapIntent(clientX, clientY, $sec);
				if (gap) {
					return {
						type: 'newRow',
						position: 'between',
						section: secRef,
						$section: $sec,
						$anchorRow: gap.$anchorRow,
						$nextRow: gap.$nextRow,
					};
				}
				fr = $field[0].getBoundingClientRect();
				rx = (clientX - fr.left) / fr.width;
				ry = (clientY - fr.top) / fr.height;
				var $grids = getRowGrids($row);
				colCount = $grids.length;
				var gridIdx = $grids.index($grid);
				if (gridIdx < 0) {
					gridIdx = 0;
				}

				if (colCount === 1 && rx < EDGE_X) {
					return {
						type: 'horizontalSplit',
						mode: 'before',
						clientY: clientY,
						section: secRef,
						$row: $row,
						$grid: $grid,
						$field: $field,
					};
				}
				if (colCount === 1 && rx > 1 - EDGE_X) {
					return {
						type: 'horizontalSplit',
						mode: 'after',
						clientY: clientY,
						section: secRef,
						$row: $row,
						$grid: $grid,
						$field: $field,
					};
				}
				if (colCount >= 2) {
					if (rx < EDGE_X) {
						if (gridIdx > 0) {
							return {
								type: 'intoGrid',
								edge: 'left',
								clientY: clientY,
								section: secRef,
								$row: $row,
								$grid: $grids.eq(gridIdx - 1),
								$field: $field,
							};
						}
						if (colCount < 4) {
							return {
								type: 'expandRow',
								side: 'left',
								edge: 'left',
								clientY: clientY,
								section: secRef,
								$row: $row,
								$grid: $grid,
								$field: $field,
							};
						}
						return {
							type: 'fieldSibling',
							sibling: 'before',
							edge: 'left',
							clientY: clientY,
							section: secRef,
							$row: $row,
							$grid: $grid,
							$field: $field,
						};
					}
					if (rx > 1 - EDGE_X) {
						if (gridIdx < colCount - 1) {
							return {
								type: 'intoGrid',
								edge: 'right',
								clientY: clientY,
								section: secRef,
								$row: $row,
								$grid: $grids.eq(gridIdx + 1),
								$field: $field,
							};
						}
						if (colCount < 4) {
							return {
								type: 'expandRow',
								side: 'right',
								edge: 'right',
								clientY: clientY,
								section: secRef,
								$row: $row,
								$grid: $grid,
								$field: $field,
							};
						}
						return {
							type: 'fieldSibling',
							sibling: 'after',
							edge: 'right',
							clientY: clientY,
							section: secRef,
							$row: $row,
							$grid: $grid,
							$field: $field,
						};
					}
				}
				if (ry < EDGE_Y_TOP) {
					return {
						type: 'fieldSibling',
						sibling: 'before',
						clientY: clientY,
						section: secRef,
						$row: $row,
						$grid: $grid,
						$field: $field,
					};
				}
				if (ry > 1 - EDGE_Y_BOTTOM) {
					return {
						type: 'fieldSibling',
						sibling: 'after',
						clientY: clientY,
						section: secRef,
						$row: $row,
						$grid: $grid,
						$field: $field,
					};
				}
				return {
					type: 'intoGrid',
					clientY: clientY,
					section: secRef,
					$row: $row,
					$grid: $grid,
				};
			}

			$grid = pickGridUnderPoint(clientX, clientY, $sec);
			if ($grid.length) {
				$row = $grid.closest('.evf-admin-row');
				if (!isRepeaterContext($grid)) {
					var gr = $grid[0].getBoundingClientRect();
					var gridEdge = null;
					if (clientY < gr.top + 18) {
						gridEdge = 'top';
					} else if (clientY > gr.bottom - 18) {
						gridEdge = 'bottom';
					}
					return {
						type: 'intoGrid',
						clientY: clientY,
						gridEdge: gridEdge,
						section: secRef,
						$row: $row,
						$grid: $grid,
					};
				}
			}
		}

		return null;
	}

	var $rowBar = null;
	var $vBar = null;

	function ensureIndicators() {
		if (!$rowBar || !$rowBar.length) {
			$rowBar = $(
				'<div class="evf-canvas-drop-indicator evf-canvas-drop-indicator--row" aria-hidden="true" />',
			);
			$('body').append($rowBar);
		}
		if (!$vBar || !$vBar.length) {
			$vBar = $(
				'<div class="evf-canvas-drop-indicator evf-canvas-drop-indicator--col" aria-hidden="true" />',
			);
			$('body').append($vBar);
		}
	}

	function hideIndicators() {
		if ($rowBar) {
			$rowBar.css('display', 'none');
		}
		if ($vBar) {
			$vBar.css('display', 'none');
		}
	}

	// Hard teardown used on drop/end to avoid “stuck” fixed-position nodes.
	function destroyIndicators() {
		if ($rowBar && $rowBar.length) {
			$rowBar.remove();
		}
		if ($vBar && $vBar.length) {
			$vBar.remove();
		}
		$rowBar = null;
		$vBar = null;
		$('.evf-canvas-drop-indicator').remove();
	}

	/**
	 * @param {EVFDropIntent|null} intent
	 * @param {object} builder - EVFPanelBuilder
	 */
	function renderDropIndicator(intent, builder) {
		ensureIndicators();
		hideIndicators();
		if (!intent) {
			if (builder && builder.hideColumnDropIndicator) {
				builder.hideColumnDropIndicator();
			}
			return;
		}
		var existingFieldDrag = !!(builder && builder._evfExistingFieldDrag);
		if (intent.type === 'horizontalSplit' && builder && builder.showColumnDropIndicator) {
			// Only show left-side split indicator (hide right-side indicator).
			if (intent.mode === 'before') {
				builder.showColumnDropIndicator(intent.$field, intent.mode);
			} else if (builder.hideColumnDropIndicator) {
				builder.hideColumnDropIndicator();
			}
			return;
		}
		if (builder && builder.hideColumnDropIndicator) {
			builder.hideColumnDropIndicator();
		}
		// Edge targeting (multi-column rows): show a vertical bar, even when staying in same column.
		if (
			(intent.type === 'intoGrid' ||
				intent.type === 'fieldSibling' ||
				intent.type === 'expandRow') &&
			intent.edge &&
			intent.$field &&
			intent.$field.length
		) {
			// Only show left edge indicator (hide right-side drop zone indicator).
			if (intent.edge !== 'left') {
				return;
			}
			var er = intent.$field[0].getBoundingClientRect();
			var x =
				intent.edge === 'left' ? Math.round(er.left - 2) : Math.round(er.right - 2);
			$vBar.css({
				display: 'block',
				position: 'fixed',
				top: Math.round(er.top) + 'px',
				left: x + 'px',
				height: Math.round(er.height) + 'px',
				width: '3px',
				background: '#2271b1',
				borderRadius: '1px',
				zIndex: 100100,
				pointerEvents: 'none',
				boxShadow: '0 0 0 1px rgba(255,255,255,.9)',
			});
			return;
		}
		if (existingFieldDrag) {
			// For existing-field drags, suppress top/bottom/row insertion indicators.
			return;
		}
		if (intent.type === 'newRow' && intent.$anchorRow && intent.$anchorRow.length) {
			var rr = intent.$anchorRow[0].getBoundingClientRect();
			var left = rr.left;
			var width = rr.width;
			var topPx = rr.bottom - 2;
			if (intent.$nextRow && intent.$nextRow.length) {
				var rr1 = intent.$nextRow[0].getBoundingClientRect();
				left = Math.min(rr.left, rr1.left);
				width = Math.max(rr.right, rr1.right) - left;
				topPx = (rr.bottom + rr1.top) / 2 - 2;
			}
			$rowBar.css({
				display: 'block',
				position: 'fixed',
				left: Math.round(left) + 'px',
				width: Math.round(width) + 'px',
				top: Math.round(topPx) + 'px',
				height: '4px',
				background: '#2271b1',
				borderRadius: '2px',
				zIndex: 100099,
				pointerEvents: 'none',
				boxShadow: '0 0 0 1px rgba(255,255,255,.9)',
			});
			return;
		}
		if (
			(intent.type === 'fieldSibling' || intent.type === 'intoGrid') &&
			intent.$grid &&
			intent.$grid.length
		) {
			var gr = intent.$grid[0].getBoundingClientRect();
			var top = gr.top;
			var h = gr.height;
			if (intent.type === 'fieldSibling' && intent.$field && intent.$field.length) {
				var fr = intent.$field[0].getBoundingClientRect();
				top =
					intent.sibling === 'before'
						? fr.top - 2
						: fr.bottom - 2;
				h = 4;
			} else {
				if (intent.gridEdge === 'top') {
					top = gr.top - 2;
				} else if (intent.gridEdge === 'bottom') {
					top = gr.bottom - 2;
				} else {
					top = gr.bottom - 6;
				}
				h = 4;
			}
			$rowBar.css({
				display: 'block',
				position: 'fixed',
				left: Math.round(gr.left) + 'px',
				width: Math.round(gr.width) + 'px',
				top: Math.round(top) + 'px',
				height: Math.round(Math.max(h, 4)) + 'px',
				background: '#2271b1',
				borderRadius: '2px',
				zIndex: 100099,
				pointerEvents: 'none',
				boxShadow: '0 0 0 1px rgba(255,255,255,.9)',
			});
		}
	}

	function clearFrozenSplit(builder) {
		if (builder && builder.clearFrozenColumnDrop) {
			builder.clearFrozenColumnDrop();
		} else if (builder) {
			builder._frozenHorizontalDrop = null;
		}
	}

	function setFrozenSplit(builder, intent) {
		if (!builder || !intent || intent.type !== 'horizontalSplit') {
			return;
		}
		builder._frozenHorizontalDrop = {
			mode: intent.mode,
			targetId: intent.$field.attr('data-field-id'),
			$row: intent.$row,
		};
	}

	/**
	 * @param {jQuery} $anchor
	 * @param {object} builder
	 * @param {Function} [done]
	 */
	function insertRowAfter($anchor, builder, done) {
		var $container = $anchor.closest('[id^="part_"]');
		if (!$container.length) {
			$container = $anchor.closest('.evf-admin-field-wrapper');
		}
		var rowIds = $container
			.find('.evf-admin-row')
			.map(function () {
				return parseInt($(this).attr('data-row-id'), 10) || 0;
			})
			.get();
		var maxId = Math.max.apply(null, rowIds.concat([0]));
		var newId = maxId + 1;
		var $proto = $container.find('.evf-admin-row').first();
		if (!$proto.length) {
			$proto = $('.evf-admin-row').first();
		}
		var $nr = $proto.clone();
		$nr.find('.evf-admin-grid').empty();
		$nr.attr('data-row-id', newId);
		$anchor.after($nr);
		if ($nr.find('.evf-admin-grid').length !== 1 && builder.setRowGridCount) {
			builder.setRowGridCount($nr, 1);
		}

		var $addMeta = $container
			.closest('.evf-admin-field-container')
			.find('.evf-add-row')
			.not('.repeater-row')
			.first();
		if ($addMeta.length) {
			var tr = parseInt($addMeta.attr('data-total-rows'), 10) || rowIds.length;
			$addMeta.attr('data-total-rows', tr + 1);
			$addMeta.attr('data-next-row-id', newId);
		}

		if (
			$('.everest-forms-row-options').length > 0 &&
			typeof evf_data !== 'undefined' &&
			evf_data.ajax_url
		) {
			$.post(evf_data.ajax_url, {
				action: 'everest_forms_new_row',
				security: evf_data.evf_add_row_nonce,
				form_id: evf_data.form_id,
				row_id: newId,
			}).done(function (xhr) {
				if (xhr && xhr.success && xhr.data && xhr.data.html) {
					$('.everest-forms-row-option-group').append(xhr.data.html);
					if (builder.conditionalLogicAppendRow) {
						builder.conditionalLogicAppendRow(String(newId));
					}
					$(
						'#everest-forms-panel-field-form_rows-connection_row_' +
							newId +
							'-conditional_logic_status',
					).prop('checked', false);
				}
			});
		}

		if (typeof done === 'function') {
			done($nr);
		}
		builder.bindFields();
	}

	function insertRowBefore($next, builder, done) {
		var $container = $next.closest('[id^="part_"]');
		if (!$container.length) {
			$container = $next.closest('.evf-admin-field-wrapper');
		}
		var rowIds = $container
			.find('.evf-admin-row')
			.map(function () {
				return parseInt($(this).attr('data-row-id'), 10) || 0;
			})
			.get();
		var maxId = Math.max.apply(null, rowIds.concat([0]));
		var newId = maxId + 1;
		var $proto = $container.find('.evf-admin-row').first();
		if (!$proto.length) {
			$proto = $('.evf-admin-row').first();
		}
		var $nr = $proto.clone();
		$nr.find('.evf-admin-grid').empty();
		$nr.attr('data-row-id', newId);
		$next.before($nr);
		if ($nr.find('.evf-admin-grid').length !== 1 && builder.setRowGridCount) {
			builder.setRowGridCount($nr, 1);
		}

		var $addMeta = $container
			.closest('.evf-admin-field-container')
			.find('.evf-add-row')
			.not('.repeater-row')
			.first();
		if ($addMeta.length) {
			var tr = parseInt($addMeta.attr('data-total-rows'), 10) || rowIds.length;
			$addMeta.attr('data-total-rows', tr + 1);
			$addMeta.attr('data-next-row-id', newId);
		}

		if (
			$('.everest-forms-row-options').length > 0 &&
			typeof evf_data !== 'undefined' &&
			evf_data.ajax_url
		) {
			$.post(evf_data.ajax_url, {
				action: 'everest_forms_new_row',
				security: evf_data.evf_add_row_nonce,
				form_id: evf_data.form_id,
				row_id: newId,
			}).done(function (xhr) {
				if (xhr && xhr.success && xhr.data && xhr.data.html) {
					$('.everest-forms-row-option-group').append(xhr.data.html);
					if (builder.conditionalLogicAppendRow) {
						builder.conditionalLogicAppendRow(String(newId));
					}
					$(
						'#everest-forms-panel-field-form_rows-connection_row_' +
							newId +
							'-conditional_logic_status',
					).prop('checked', false);
				}
			});
		}

		if (typeof done === 'function') {
			done($nr);
		}
		builder.bindFields();
	}

	/**
	 * Place palette clone and invoke fieldDrop.
	 */
	function placePaletteField(builder, $button, intent) {
		var $clone = $button.clone(false, false);
		$clone.removeAttr('id');
		$clone.css({ width: '', left: '', top: '' });

		clearFrozenSplit(builder);

		if (intent.type === 'horizontalSplit') {
			if (intent.mode === 'before') {
				intent.$field.before($clone);
			} else {
				intent.$field.after($clone);
			}
			setFrozenSplit(builder, intent);
		} else if (intent.type === 'fieldSibling') {
			if (intent.sibling === 'before') {
				intent.$field.before($clone);
			} else {
				intent.$field.after($clone);
			}
		} else if (intent.type === 'intoGrid') {
			// If we have a pointer Y, insert at nearest vertical position for better “before/after” feel.
			if (typeof intent.clientY === 'number') {
				insertIntoGridAtY(intent.$grid, intent.clientY, $clone);
			} else {
				intent.$grid.append($clone);
			}
		} else if (intent.type === 'expandRow') {
			// Add a column (up to 4) and place into the new column.
			var $grids = expandRowColumns(builder, intent.$row, intent.side);
			if ($grids && $grids.length) {
				var $targetGrid =
					intent.side === 'left' ? $grids.eq(0) : $grids.eq($grids.length - 1);
				if (typeof intent.clientY === 'number') {
					insertIntoGridAtY($targetGrid, intent.clientY, $clone);
				} else {
					$targetGrid.append($clone);
				}
			} else {
				// fallback: insert before/after within same grid
				if (intent.edge === 'left') {
					intent.$field.before($clone);
				} else {
					intent.$field.after($clone);
				}
			}
		} else if (intent.type === 'newRow') {
			/* async */
			return false;
		} else if (intent.type === 'emptySection') {
			return false;
		} else {
			return false;
		}

		builder.fieldDrop($clone);
		return true;
	}

	function handleNewRowDrop(builder, $button, intent) {
		if (intent.position === 'before' && intent.$nextRow && intent.$nextRow.length) {
			insertRowBefore(intent.$nextRow, builder, function ($nr) {
				var $g = $nr.find('.evf-admin-grid').first();
				var $clone = $button.clone(false, false);
				$clone.removeAttr('id');
				$g.append($clone);
				clearFrozenSplit(builder);
				builder.fieldDrop($clone);
			});
			return;
		}

		// between / after defaults: insert after anchor row (or last row).
		insertRowAfter(intent.$anchorRow, builder, function ($nr) {
			var $g = $nr.find('.evf-admin-grid').first();
			var $clone = $button.clone(false, false);
			$clone.removeAttr('id');
			$g.append($clone);
			clearFrozenSplit(builder);
			builder.fieldDrop($clone);
		});
	}

	var dragState = {
		active: false,
		$button: null,
		lastIntent: null,
		pointer: { x: 0, y: 0 },
	};

	function onPaletteDragMove(e, builder) {
		if (!dragState.active) {
			return;
		}
		var ev = e.originalEvent || e;
		var x = ev.clientX;
		var y = ev.clientY;
		dragState.pointer.x = x;
		dragState.pointer.y = y;
		dragState.lastIntent = computeDropIntent(x, y);
		renderDropIndicator(dragState.lastIntent, builder);
	}

	function onPaletteDragStop(builder, accepted, $btn) {
		dragState.active = false;
		$(document).off('.evfCanvasDrop');
		destroyIndicators();
		if (builder && builder.hideColumnDropIndicator) {
			builder.hideColumnDropIndicator();
		}
		if ($btn && $btn.length) {
			$btn.removeClass('field-dragged');
		} else if (!accepted && dragState.$button) {
			dragState.$button.removeClass('field-dragged');
		}
		dragState.$button = null;
		dragState.lastIntent = null;
		dragState.pointer = { x: 0, y: 0 };
	}

	/**
	 * Replace default palette draggable with canvas-aware behavior.
	 * @param {object} builder - EVFPanelBuilder
	 */
	function hookAfterBindFields(builder) {
		if (!canvasDropEnabled()) {
			return;
		}

		var $buttons = $(
			'.evf-registered-buttons button.evf-registered-item',
		).not('.evf-repeater-field');

		if ($buttons.length && $.fn.draggable && $buttons.data('ui-draggable')) {
			try {
				$buttons.draggable('destroy');
			} catch (err) {
				/* ignore */
			}
		}

		$buttons.draggable({
			delay: 200,
			cancel: false,
			scroll: false,
			revert: false,
			scrollSensitivity: 40,
			forcePlaceholderSize: true,
			appendTo: 'body',
			zIndex: 100200,
			helper: function () {
				var $h = $(this)
					.clone()
					.insertAfter(
						$(this)
							.closest('.everest-forms-tab-content')
							.siblings('.everest-forms-fields-tab'),
					);
				$h.addClass('evf-drag-helper');
				$h.css({
					display: 'block',
					visibility: 'visible',
					opacity: 0.95,
					position: 'fixed',
					zIndex: 100200,
					pointerEvents: 'none',
					background: '#fff',
					border: '1px solid #cdd0d8',
					boxShadow: '0 8px 24px rgba(0,0,0,0.12)',
					padding: '12px 14px',
					minHeight: '40px',
				});
				return $h;
			},
			start: function (e) {
				var $btn = $(this);
				var ev0 = e.originalEvent || e;
				dragState.active = true;
				dragState.$button = $btn;
				dragState.pointer.x = ev0.clientX;
				dragState.pointer.y = ev0.clientY;
				$btn.addClass('field-dragged');
				$('.evf-drag-helper').css({
					display: 'block',
					visibility: 'visible',
				});
				$(document).on(
					'mousemove.evfCanvasDrop drag.evfCanvasDrop',
					function (ev) {
						onPaletteDragMove(ev, builder);
					},
				);
			},
			stop: function () {
				var $btn = $(this);
				var intent =
					dragState.lastIntent ||
					computeDropIntent(
						dragState.pointer.x,
						dragState.pointer.y,
					);
				var accepted = false;

				if (intent && intent.type === 'newRow') {
					handleNewRowDrop(builder, $btn, intent);
					accepted = true;
				} else if (intent && intent.type === 'emptySection') {
					var $add = intent.$section
						.closest('.evf-admin-field-container')
						.find('.evf-add-row')
						.not('.repeater-row')
						.first()
						.find('span');
					if ($add.length) {
						$add.trigger('click');
					}
					accepted = false;
				} else if (intent && intent.type !== 'emptySection') {
					accepted = placePaletteField(builder, $btn, intent);
				}

				onPaletteDragStop(builder, accepted, $btn);
			},
			opacity: 0.75,
			containment: '#everest-forms-builder',
		});

		$('.evf-registered-item.evf-repeater-field').draggable(
			'option',
			'connectToSortable',
			'.evf-repeatable-grid',
		);
	}

	/**
	 * Serialize current DOM into a plain model (for debugging / future sync).
	 */
	function readLayoutModel() {
		var sections = [];
		getSectionRoots().each(function () {
			var $sec = $(this);
			var rows = [];
			$sec.find('.evf-admin-row').each(function () {
				var $row = $(this);
				var cols = [];
				$row.find('.evf-grid-lists > .evf-admin-grid').each(function () {
					var $g = $(this);
					var ids = [];
					$g.children('.everest-forms-field').each(function () {
						ids.push($(this).attr('data-field-id'));
					});
					cols.push({
						gridId: $g.attr('data-grid-id'),
						fieldIds: ids,
					});
				});
				rows.push({
					rowId: $row.attr('data-row-id'),
					columns: cols,
				});
			});
			sections.push({
				id: $sec.attr('data-part-id') || $sec.attr('id') || 'main',
				rows: rows,
			});
		});
		return { sections: sections };
	}

	window.EVFCanvasDrop = {
		canvasDropEnabled: canvasDropEnabled,
		getSectionRoots: getSectionRoots,
		getRowGrids: getRowGrids,
	insertIntoGridAtY: insertIntoGridAtY,
		insertRowAfter: insertRowAfter,
		insertRowBefore: insertRowBefore,
		expandRowColumns: expandRowColumns,
		computeDropIntent: computeDropIntent,
		renderDropIndicator: renderDropIndicator,
		hideIndicators: hideIndicators,
		hookAfterBindFields: hookAfterBindFields,
		readLayoutModel: readLayoutModel,
		EDGE_X: EDGE_X,
	};
})(jQuery, window);
