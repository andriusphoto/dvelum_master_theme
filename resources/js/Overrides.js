Ext.define('app.fileUploadWindowOverride', {
    override: 'app.fileUploadWindow',
    initComponent: function() {
        this.callParent();
        this.multipleUpload.setHeight(100);
        this.simpleUpload.setHeight(100);
    }
});
Ext.define('app.crud.modules.CreateWindowOverride', {
    override: 'app.crud.modules.CreateWindow',
    initComponent: function() {
        this.callParent();
        this.setHeight(130);
    }
});