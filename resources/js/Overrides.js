Ext.define('app.fileUploadWindowOverride', {
    override: 'app.fileUploadWindow',
    initComponent: function() {
        this.callParent();
        this.multipleUpload.setHeight(100);
        this.simpleUpload.setHeight(100);
    }
});