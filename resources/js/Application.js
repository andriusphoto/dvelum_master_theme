/* global Ext, developmentMode */

app.application = false;
app.content = Ext.create('Ext.Panel', {
    frame: false,
    border: false,
    layout: 'fit',
    //margins: '0 5 0 0',
    scrollable: false,
    items: [],
    collapsible: false,
    flex: 1
});
app.header = Ext.create('Ext.Panel', {
    ui: 'wm-menu-panel',
    contentEl: 'header',
    bodyCls: 'formBody',
    cls: 'adminHeader',
    height: 30
});

app.cookieProvider = new Ext.state.CookieProvider({
    expires: new Date(new Date().getTime() + (1000 * 60 * 60 * 24)) //1 day
});

Ext.application({
    name: 'DVelum',
    launch: function() {
        app.application = this;
        app.header = app.header;
        app.menu = Ext.createWidget('panel', {
            menuData: app.menuData,
            region: 'west',
            width: 250,
            devMode: developmentMode,
            split: true,
            reference: 'treelistContainer',
            itemId: 'app_menu',
            layout: {
                type: 'absolute',

            },
            scrollable: false,
            border: false,
            scrollable: false,
            ui: 'wm-menu-panel',
            dock: 'left',
            stateful: true,
            stateId: '_appXMasterMenuState',
            stateEvents: ['toggle'],
            dockedItems: [

                {
                    xtype: 'button',
                    ui: 'wm-menu-button-small',
                    iconCls: 'x-fa fa-angle-left',
                    height: 44,
                    dock: 'top',
                    enableToggle: true,
                    stateful: true,
                    stateId: '_appXMButtonState',
                    getState: function() {
                        return { pressed: this.pressed };
                    },

                    applyState: function(state) {
                        this.toggle(state.pressed);
                    },
                    toggleHandler: function(button, pressed) {
                        var menu = button.up('#app_menu'),
                            treelist = menu.down('treelist'),
                            container = treelist.up('container'),
                            navBtn = button,
                            ct = treelist.ownerCt;
                        if (pressed) {
                            navBtn.setPressed(true);
                            navBtn.setIconCls('x-fa fa-angle-right');

                            menu.setWidth(40);

                        } else {
                            navBtn.setIconCls('x-fa fa-angle-left');
                            container.setWidth(265);
                            menu.setWidth(250);
                        }
                        menu.fireEvent('toggle');

                    }
                }
            ],
            items: [{
                xtype: 'container',
                layout: {
                    type: 'vbox',
                    align: 'stretch'


                },
                y: 0,
                x: 0,
                width: 265,
                height: '100%',
                scrollable: 'y',
                items: [{
                    xtype: 'treelist',
                    expanderFirst: false,
                    highlightPath: true,
                    reference: 'treelist',
                    ui: 'nav',
                    store: Ext.create('Ext.data.TreeStore', {
                        root: {
                            expanded: true,
                            children: app.menuData
                        }
                    }),
                    listeners: {
                        itemclick: function(cmp, record, item, index, e, eOpts) {
                            window.location.replace(record.node.get('url'));
                        }
                    }
                }]
            }]
        });
        app.content.addDocked(app.menu);
        app.viewport = Ext.create('Ext.container.Viewport', {
            cls: 'formBody',
            layout: {
                type: 'vbox',
                pack: 'start',
                align: 'stretch'
            },
            items: [app.header, app.content]
        });
    }
});