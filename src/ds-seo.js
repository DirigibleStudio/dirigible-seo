function getSeoSeparator() {
  if (
    typeof ds_seo_ajax !== 'undefined' &&
    ds_seo_ajax.separator != null &&
    String(ds_seo_ajax.separator) !== ''
  ) {
    return String(ds_seo_ajax.separator);
  }
  return '-';
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function applySeoTokens(str, pageTitle, siteName, separator) {
  if (!str || str.indexOf('{') === -1) {
    return str;
  }

  var sep = separator != null && String(separator) !== '' ? String(separator) : '-';
  var title = pageTitle != null ? String(pageTitle) : '';
  var site = siteName != null ? String(siteName) : '';

  return str
    .replace(/\{[Tt]itle\}|\{[Pp]age\}/g, title)
    .replace(/\{[Ss]ite\}/g, site)
    .replace(/\{[Ss]eparator\}/g, sep)
    .replace(/\{[Ss]ep\}/g, sep)
    .replace(/\{\|\}/g, sep)
    .replace(/\{-\}/g, sep);
}

function valueToEditorHtml(value, sep) {
  if (!value) {
    return '';
  }
  sep = sep || getSeoSeparator();

  var tokenRe =
    /(\{[Tt]itle\}|\{[Pp]age\}|\{[Ss]ite\}|\{[Ss]ep(?:arator)?\}|\{\|\}|\{-\})/g;
  var parts = value.split(tokenRe);
  var html = '';
  var i;
  var p;

  for (i = 0; i < parts.length; i++) {
    p = parts[i];
    if (!p) {
      continue;
    }

    if (/^\{[Tt]itle\}$|^\{[Pp]age\}$/.test(p)) {
      html +=
        '<span class="seo-pill seo-pill--title" contenteditable="false" data-token="' +
        escapeHtml(p) +
        '">Title</span>';
    } else if (/^\{[Ss]ite\}$/.test(p)) {
      html +=
        '<span class="seo-pill seo-pill--site" contenteditable="false" data-token="' +
        escapeHtml(p) +
        '">Site</span>';
    } else if (/^\{[Ss]ep(?:arator)?\}$|^\{\|\}$|^\{-\}$/.test(p)) {
      html +=
        '<span class="seo-pill seo-pill--sep" contenteditable="false" data-token="' +
        escapeHtml(p) +
        '">' +
        escapeHtml(sep) +
        '</span>';
    } else {
      html += escapeHtml(p);
    }
  }

  return html;
}

function extractEditorValue(node) {
  var val = '';
  var child;
  var i;

  for (i = 0; i < node.childNodes.length; i++) {
    child = node.childNodes[i];
    if (child.nodeType === 3) {
      val += child.textContent;
    } else if (child.nodeType === 1) {
      if (child.dataset && child.dataset.token) {
        val += child.dataset.token;
      } else if (child.tagName !== 'BR') {
        val += extractEditorValue(child);
      }
    }
  }

  return val;
}

function makePillSpan(token, sep) {
  sep = sep || getSeoSeparator();
  var span = document.createElement('span');
  span.contentEditable = 'false';
  span.dataset.token = token;

  if (/^\{[Tt]itle\}$|^\{[Pp]age\}$/.test(token)) {
    span.className = 'seo-pill seo-pill--title';
    span.textContent = 'Title';
  } else if (/^\{[Ss]ite\}$/.test(token)) {
    span.className = 'seo-pill seo-pill--site';
    span.textContent = 'Site';
  } else {
    span.className = 'seo-pill seo-pill--sep';
    span.textContent = sep;
  }

  return span;
}

var SEO_TITLE_PLACEHOLDER = 'Enter a title';

function removePhantomPlaceholder(editor) {
  var first = editor.firstChild;
  if (
    first &&
    first.nodeType === 3 &&
    first.textContent === SEO_TITLE_PLACEHOLDER
  ) {
    editor.removeChild(first);
  }
}

function updatePlaceholder(editor, val) {
  var wrap = editor.closest('.seo-title-input-wrap');
  if (wrap) {
    wrap.classList.toggle('is-empty', !val || !String(val).trim());
  }
}

function notifySeoTitleChanged(hidden) {
  hidden.dispatchEvent(new Event('input', { bubbles: true }));
}

function insertPillAtCaret(editor, hiddenInput, token) {
  editor.focus();
  var span = makePillSpan(token);
  var sel = window.getSelection();
  var range;

  if (
    sel &&
    sel.rangeCount &&
    editor.contains(sel.getRangeAt(0).commonAncestorContainer)
  ) {
    range = sel.getRangeAt(0);
    range.deleteContents();
    range.insertNode(span);
  } else {
    editor.appendChild(span);
  }

  if (sel) {
    range = document.createRange();
    range.setStartAfter(span);
    range.collapse(true);
    sel.removeAllRanges();
    sel.addRange(range);
  }

  removePhantomPlaceholder(editor);
  hiddenInput.value = extractEditorValue(editor);
  updatePlaceholder(editor, hiddenInput.value);
  notifySeoTitleChanged(hiddenInput);
}

function initSeoTitleEditor() {
  var editor = document.getElementById('ds_seo_title_editor');
  var hidden = document.getElementById('ds_seo_title');
  var wrap = document.getElementById('ds-editor-seo-title');

  if (!editor || !hidden || editor.dataset.seoTitleInit === '1') {
    return false;
  }

  editor.dataset.seoTitleInit = '1';

  removePhantomPlaceholder(editor);

  if (hidden.value) {
    editor.innerHTML = valueToEditorHtml(hidden.value, getSeoSeparator());
  }
  updatePlaceholder(editor, hidden.value);

  editor.addEventListener('input', function () {
    removePhantomPlaceholder(editor);
    var val = extractEditorValue(editor);
    hidden.value = val;
    updatePlaceholder(editor, val);
    notifySeoTitleChanged(hidden);
  });

  editor.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
    }
  });

  editor.addEventListener('paste', function (e) {
    e.preventDefault();
    var text = (e.clipboardData || window.clipboardData).getData('text/plain');
    if (!text) {
      return;
    }
    if (!document.execCommand('insertText', false, text)) {
      var sel = window.getSelection();
      if (sel && sel.rangeCount) {
        var r = sel.getRangeAt(0);
        r.deleteContents();
        r.insertNode(document.createTextNode(text));
        r.collapse(false);
        sel.removeAllRanges();
        sel.addRange(r);
      }
    }
    hidden.value = extractEditorValue(editor);
    updatePlaceholder(editor, hidden.value);
    notifySeoTitleChanged(hidden);
  });

  if (wrap) {
    var sepInsertBtn = wrap.querySelector('.seo-pill--insert.seo-pill--sep');
    if (sepInsertBtn) {
      sepInsertBtn.textContent = getSeoSeparator();
    }

    wrap.querySelectorAll('.seo-pill--insert').forEach(function (btn) {
      btn.addEventListener('mousedown', function (e) {
        e.preventDefault();
        insertPillAtCaret(editor, hidden, btn.dataset.token);
      });
    });
  }

  return true;
}

function onReady(fn) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fn);
  } else {
    fn();
  }
}

function dsPost(data) {
  var body = new URLSearchParams();
  Object.keys(data).forEach(function (key) {
    body.append(key, data[key]);
  });
  return fetch(ajaxurl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
    },
    body: body.toString(),
    credentials: 'same-origin',
  }).then(function (response) {
    return response.text();
  });
}

function buildPreview(box) {
  var titleInput = document.getElementById('ds_seo_title');
  var descriptionInput = document.querySelector(
    '#ds-editor-seo-description textarea'
  );
  var noIndexInput = document.querySelector(
    '#ds-editor-seo-no-index input[type="checkbox"]'
  );

  if (!titleInput || !descriptionInput || !box) {
    return;
  }

  var sep = getSeoSeparator();
  var siteName =
    ds_seo_ajax && ds_seo_ajax.site ? String(ds_seo_ajax.site) : '';
  var pgTitle =
    ds_seo_ajax && ds_seo_ajax.page_title
      ? String(ds_seo_ajax.page_title)
      : '';
  var defDesc =
    ds_seo_ajax && ds_seo_ajax.default_description
      ? String(ds_seo_ajax.default_description)
      : '';
  var link =
    ds_seo_ajax && ds_seo_ajax.permalink
      ? String(ds_seo_ajax.permalink)
      : 'https://example.com/';

  var rawTitle = titleInput.value;
  var resolvedTitle;
  if (rawTitle) {
    resolvedTitle = applySeoTokens(rawTitle, pgTitle, siteName, sep);
  } else {
    resolvedTitle = pgTitle
      ? pgTitle + ' ' + sep + ' ' + siteName
      : 'Enter a Title ' + sep + ' ' + siteName;
  }

  var rawDesc = descriptionInput.value;
  var resolvedDesc = rawDesc
    ? applySeoTokens(rawDesc, pgTitle, siteName, sep)
    : defDesc || '';

  var noIndex = noIndexInput && noIndexInput.checked;

  box.innerHTML =
    '<div class="ds-seo-preview' +
    (noIndex ? ' no-index' : '') +
    '">' +
    '<span class="title">' +
    escapeHtml(resolvedTitle) +
    '</span>' +
    '<span class="link">' +
    escapeHtml(link) +
    '</span>' +
    '<span class="description">' +
    escapeHtml(resolvedDesc) +
    '</span>' +
    '</div>';
}

function initSeoPreview() {
  var box = document.getElementById('ds-editor-seo-preview');
  if (!box || box.dataset.seoPreviewInit === '1') {
    return false;
  }

  var titleInput = document.getElementById('ds_seo_title');
  var descriptionInput = document.querySelector(
    '#ds-editor-seo-description textarea'
  );
  var noIndexInput = document.querySelector(
    '#ds-editor-seo-no-index input[type="checkbox"]'
  );

  if (!titleInput || !descriptionInput) {
    return false;
  }

  box.dataset.seoPreviewInit = '1';
  buildPreview(box);

  titleInput.addEventListener('input', function () {
    buildPreview(box);
  });
  descriptionInput.addEventListener('input', function () {
    buildPreview(box);
  });
  if (noIndexInput) {
    noIndexInput.addEventListener('change', function () {
      buildPreview(box);
    });
  }

  return true;
}

function initCanonicalToggle() {
  var enable = document.getElementById('ds_seo_custom_canonical_enabled');
  var urlField = document.getElementById('ds-editor-seo-canonical-url');
  if (!enable || !urlField || enable.dataset.seoCanonicalInit === '1') {
    return false;
  }

  enable.dataset.seoCanonicalInit = '1';

  function toggleCanonicalUrl() {
    urlField.style.display = enable.checked ? '' : 'none';
  }

  enable.addEventListener('change', toggleCanonicalUrl);
  toggleCanonicalUrl();
  return true;
}

function initMigrateYoast() {
  var button = document.getElementById('ds-migrate-yoast');
  if (!button || button.dataset.seoMigrateInit === '1') {
    return false;
  }

  button.dataset.seoMigrateInit = '1';
  button.addEventListener('click', function () {
    var section = button.closest('.tool');
    if (!section) {
      return;
    }

    section.querySelectorAll('.notification').forEach(function (el) {
      el.remove();
    });

    dsPost({ action: 'ds_migrate_yoast' }).then(function (response) {
      var json = JSON.parse(response);
      json.forEach(function (entry) {
        section.insertAdjacentHTML('beforeend', entry);
      });
    });
  });

  return true;
}

function initLlmsTxt() {
  var saveBtn = document.getElementById('ds-save-llms-txt');
  var loadBtn = document.getElementById('ds-load-llms-txt');
  var deleteBtn = document.getElementById('ds-delete-llms-txt');
  if (!saveBtn || saveBtn.dataset.seoLlmsInit === '1') {
    return false;
  }

  saveBtn.dataset.seoLlmsInit = '1';

  function setStatus(statusEl, html) {
    if (statusEl) {
      statusEl.innerHTML = html;
    }
  }

  saveBtn.addEventListener('click', function () {
    var status = document.getElementById('llms-txt-status');
    var content = document.getElementById('llms-txt-content');

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    setStatus(status, '');

    dsPost({
      action: 'ds_save_llms_txt',
      content: content ? content.value : '',
      nonce: ds_seo_ajax.nonce,
    })
      .then(function (response) {
        var json = JSON.parse(response);
        var statusClass = json.success ? 'notice-success' : 'notice-error';
        setStatus(
          status,
          '<div style="margin-top: 8px;" class="notice ' +
            statusClass +
            '"><p>' +
            json.message +
            '</p></div>'
        );
      })
      .catch(function () {
        setStatus(
          status,
          '<div class="notice notice-error"><p>Error saving file. Please try again.</p></div>'
        );
      })
      .finally(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save llms.txt';
      });
  });

  if (loadBtn) {
    loadBtn.addEventListener('click', function () {
      var status = document.getElementById('llms-txt-status');
      var textarea = document.getElementById('llms-txt-content');

      loadBtn.disabled = true;
      loadBtn.textContent = 'Loading...';
      setStatus(status, '');

      dsPost({
        action: 'ds_load_llms_txt',
        nonce: ds_seo_ajax.nonce,
      })
        .then(function (response) {
          var json = JSON.parse(response);
          if (json.success) {
            if (textarea) {
              textarea.value = json.content;
            }
            setStatus(
              status,
              '<div class="notice notice-success"><p>Content reloaded from file.</p></div>'
            );
          } else {
            setStatus(
              status,
              '<div class="notice notice-error"><p>Error loading file.</p></div>'
            );
          }
        })
        .catch(function () {
          setStatus(
            status,
            '<div class="notice notice-error"><p>Error loading file. Please try again.</p></div>'
          );
        })
        .finally(function () {
          loadBtn.disabled = false;
          loadBtn.textContent = 'Reload from File';
        });
    });
  }

  if (deleteBtn) {
    deleteBtn.addEventListener('click', function () {
      if (
        !confirm(
          'Are you sure you want to delete the llms.txt file? This action cannot be undone.'
        )
      ) {
        return;
      }

      var status = document.getElementById('llms-txt-status');
      var textarea = document.getElementById('llms-txt-content');

      deleteBtn.disabled = true;
      deleteBtn.textContent = 'Deleting...';
      setStatus(status, '');

      dsPost({
        action: 'ds_delete_llms_txt',
        nonce: ds_seo_ajax.nonce,
      })
        .then(function (response) {
          var json = JSON.parse(response);
          var statusClass = json.success ? 'notice-success' : 'notice-error';
          setStatus(
            status,
            '<div style="margin-top: 8px;" class="notice ' +
              statusClass +
              '"><p>' +
              json.message +
              '</p></div>'
          );
          if (json.success && textarea) {
            textarea.value = '';
          }
        })
        .catch(function () {
          setStatus(
            status,
            '<div class="notice notice-error"><p>Error deleting file. Please try again.</p></div>'
          );
        })
        .finally(function () {
          deleteBtn.disabled = false;
          deleteBtn.textContent = 'Delete llms.txt';
        });
    });
  }

  return true;
}

function observeSeoMetaBox() {
  var metaBox = document.getElementById('ds-seo-meta-box');
  if (!metaBox || typeof MutationObserver === 'undefined') {
    return;
  }

  var root = metaBox.closest('.postbox') || metaBox.parentElement;
  if (!root || root.dataset.seoMetaObserved === '1') {
    return;
  }

  root.dataset.seoMetaObserved = '1';
  new MutationObserver(function () {
    initSeoTitleEditor();
    initSeoPreview();
    initCanonicalToggle();
  }).observe(root, {
    attributes: true,
    attributeFilter: ['class', 'style'],
    childList: true,
    subtree: true,
  });
}

function initAll() {
  initSeoTitleEditor();
  initSeoPreview();
  initCanonicalToggle();
  initMigrateYoast();
  initLlmsTxt();
}

onReady(function () {
  initAll();
  setTimeout(initAll, 0);
  setTimeout(initAll, 500);
  observeSeoMetaBox();

  document.addEventListener('click', function (e) {
    if (
      e.target.closest(
        '.postbox .handlediv, .postbox .postbox-header, .postbox-toggle'
      )
    ) {
      setTimeout(initAll, 0);
    }
  });
});
