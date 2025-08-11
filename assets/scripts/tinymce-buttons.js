/**
 * Custom Buttons
 */
tinymce.PluginManager.add('wp_custom_theme_tc_button', function( editor, url ) {
  editor.addButton( 'wp_custom_theme_tc_button', {
    icon: '',
    text: "Add Button",
    classes: 'bs-content-button',
    title: "Insert Button",
    onclick: function() {
      editor.windowManager.open( {
        title: 'Insert Button',
        width: 500,
        height: 300,
        body: [
          {
            type: 'textbox',
            name: 'url',
            label: 'URL'
          },
          {
            type: 'textbox',
            name: 'label',
            label: 'Link Text'
          },
          {
            type: 'checkbox',
            name: 'newtab',
            label: ' ',
            text: 'Open link in new tab',
            checked: false
          },
          {
            type: 'listbox',
            name: 'style',
            label: 'Button Style',
            'values': [
              { text: "Primary", value: "btn-primary" },
              { text: "Secondary", value: "btn-secondary" },
            ]
          }],
        onsubmit: function( e ) {
          let $content = '<a role="button" href="' + e.data.url + '" class="btn' + (e.data.style !== 'default' ? ' ' + e.data.style : '') + '"' + (!!e.data.newtab ? ' target="_blank"' : '' ) + '>' + e.data.label + '</a>';
          editor.insertContent( $content );
        }
      });
    }
  });
});