<?php

function ccpHasClass(DOMElement $element, $class) {
    return in_array($class, preg_split('/\s+/', trim($element->getAttribute('class'))), true);
}

function ccpLayoutDom($html) {
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    try {
        $document->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>', LIBXML_NONET);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
    return new DOMXPath($document);
}

function ccpExtensionFixture($installed = true) {
    $child = array('name'=>'Child', 'status'=>'Enabled', 'edit'=>'#edit', 'delete'=>'#delete');
    return array(
        'name'=>'Example', 'status'=>'Enabled', 'installed'=>$installed, 'sort_order'=>1,
        'date_uploaded'=>'2026-01-02 03:04:05', 'date_installed'=>'2026-02-03 04:05:06',
        'date_uploaded_label'=>'02/01/2026', 'date_installed_label'=>'03/02/2026',
        'edit'=>'#edit', 'install'=>'#install', 'uninstall'=>'#uninstall', 'link'=>'#link',
        'module'=>array($child), 'store'=>array($child)
    );
}

/** Render actual Twig branches before inspecting table structure, not source formatting. */
function ccpCheckExtensionTable(\Twig\Environment $twig, $source) {
    $errors = array();
    foreach (array('empty'=>array(), 'installed'=>array(ccpExtensionFixture()), 'uninstalled'=>array(ccpExtensionFixture(false))) as $scenario=>$extensions) {
        $html = $twig->createTemplate($source)->render(array('extensions'=>$extensions, 'column_action'=>'QA action', 'text_no_results'=>'QA empty'));
        $xpath = ccpLayoutDom($html);
        $tables = $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " ccp-extension-table ")]');
        if ($tables->length !== 1) { $errors[] = $scenario . ': expected one extension table'; continue; }
        $table = $tables->item(0);
        $headers = $xpath->query('./thead/tr/th', $table);
        $columns = $headers->length;
        if ($xpath->query('./thead', $table)->length !== 1 || $columns < 6 || $columns > 8 || !ccpHasClass($table, 'ccp-extension-cols-' . $columns)) {
            $errors[] = $scenario . ': expected 6-8 headers and matching table width class';
        }
        $keys = array();
        foreach ($xpath->query('./thead/tr/th/button[@data-ccp-sort]', $table) as $button) {
            $key = $button->getAttribute('data-ccp-sort');
            $keys[] = $key;
            if (!ccpHasClass($button, 'ccp-extension-sort') || $button->getAttribute('type') !== 'button') {
                $errors[] = $scenario . ': sort control must be a button with the sorting class';
            }
            if (in_array($key, array('status','upload','date'), true) && !ccpHasClass($button->parentNode, 'text-center')) {
                $errors[] = $scenario . ': heading ' . $key . ' needs text-center';
            }
        }
        sort($keys);
        if ($keys !== array('date','installed','name','status','upload')) {
            $errors[] = $scenario . ': expected exactly five sort keys: name, status, installed, upload, date';
        }
        $action = $headers->item($columns - 1);
        if (!$action || trim($action->textContent) !== 'QA action' || !ccpHasClass($action, 'text-center') || $action->getAttribute('scope') !== 'col') {
            $errors[] = $scenario . ': action header needs its label, scope and text-center';
        }
        $rows = $xpath->query('./tbody/tr', $table);
        if ($rows->length === 0) { $errors[] = $scenario . ': table body has no rows'; }
        $mainRows = 0;
        foreach ($rows as $row) {
            $cells = $xpath->query('./td', $row);
            if ($scenario === 'empty') {
                if ($rows->length !== 1 || $cells->length !== 1 || $cells->item(0)->getAttribute('colspan') !== (string)$columns || trim($row->textContent) !== 'QA empty') {
                    $errors[] = 'empty: expected one message spanning all header columns';
                }
                continue;
            }
            if ($cells->length !== $columns) { $errors[] = $scenario . ': body cell count differs from header'; }
            foreach ($cells as $cell) {
                if (($cell->hasAttribute('colspan') && $cell->getAttribute('colspan') !== '1') || ($cell->hasAttribute('rowspan') && $cell->getAttribute('rowspan') !== '1')) {
                    $errors[] = $scenario . ': extension rows must have one cell per column';
                }
            }
            if (ccpHasClass($row, 'ccp-extension-main')) {
                $mainRows++;
                foreach (array('name','status','installed','upload','date') as $key) {
                    if (!$row->hasAttribute('data-sort-' . $key)) { $errors[] = $scenario . ': missing row sort value ' . $key; }
                }
            }
        }
        if ($scenario !== 'empty' && $mainRows !== 1) { $errors[] = $scenario . ': fixture extension must render one sortable main row'; }
    }
    return array_values(array_unique($errors));
}

/** Static CSS declaration contract only; browser geometry and the cascade need browser QA. */
function ccpCheckExtensionParent(\Twig\Environment $twig, $source) {
    $html = $twig->createTemplate($source)->render(array('header'=>'<header id="qa-header"></header>', 'column_left'=>'<nav id="column-left"></nav>'));
    $xpath = ccpLayoutDom($html);
    $errors = array();
    $sidebar = $xpath->query('//*[@id="column-left"]');
    $contents = $xpath->query('//*[@id="content"]');
    if ($sidebar->length !== 1 || $contents->length !== 1 || $xpath->query('following-sibling::*[1][@id="content"]', $sidebar->item(0))->length !== 1) {
        $errors[] = 'content must be the next element sibling of column-left';
    }
    $rules = array();
    foreach ($xpath->query('//style') as $style) {
        // Parse the bundled plain CSS AST without evaluating Sass or resolving imports.
        $stylesheet = \ScssPhp\ScssPhp\Ast\Sass\Statement\Stylesheet::parseCss($style->textContent);
        foreach ($stylesheet->getChildren() as $rule) {
            if (!$rule instanceof \ScssPhp\ScssPhp\Ast\Sass\Statement\StyleRule) { continue; }
            foreach (explode(',', $rule->getSelector()->getAsPlain()) as $selector) {
                $selector = preg_replace('/\s+/', ' ', trim($selector));
                foreach ($rule->getChildren() as $declaration) {
                    if ($declaration instanceof \ScssPhp\ScssPhp\Ast\Sass\Statement\Declaration) {
                        $property = strtolower(trim($declaration->getName()->getAsPlain()));
                        $rules[$selector][$property] = strtolower(trim((string)$declaration->getValue()));
                    }
                }
            }
        }
    }
    $required = array(
        '#extension .table-responsive'=>array('overflow-x'=>'auto'),
        '#extension .ccp-extension-table'=>array('table-layout'=>'fixed'),
        '#extension .ccp-extension-table thead th:last-child'=>array('text-align'=>'center')
    );
    foreach ($required as $selector=>$declarations) {
        foreach ($declarations as $property=>$value) {
            if (($rules[$selector][$property] ?? null) !== $value) { $errors[] = 'missing CSS declaration: ' . $selector . ' { ' . $property . ': ' . $value . ' }'; }
        }
    }
    foreach (array(6,7,8) as $columns) {
        for ($column = 1; $column <= $columns; $column++) {
            $selector = '#extension .ccp-extension-cols-' . $columns . ' th:nth-child(' . $column . ')';
            if (!isset($rules[$selector]['width']) || !preg_match('/^(?:\d+(?:\.\d+)?|\.\d+)%$/D', $rules[$selector]['width']) || (float)$rules[$selector]['width'] <= 0) {
                $errors[] = 'missing positive percentage width: ' . $selector;
            }
        }
    }
    return $errors;
}
