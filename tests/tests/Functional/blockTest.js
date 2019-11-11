module.exports = {
    '@tags': ['basic', 'block'],
    'basicBlock' : function (browser) {
        browser.url(browser.launch_url)
            //login
            .drupalLogin('test','test')

            //create basic block
            .drupalRelativeURL('/block/add/basic')
            .setValue('input[name="info[0][value]"]', 'Test Basic Block')
            .setValue('textarea[name="body[0][value]"]', '<p>Test Basic Block with Test Content </p>')
            .submitForm('#edit-submit')
            .assert.containsText('body', 'Basic block Test Basic Block has been created.', 'Successfully Created Basic Block')

            // edit basic block
            .drupalRelativeURL('/admin/structure/block/block-content')
            .click('link text','Test Basic Block')
            .setValue('textarea[name="body[0][value]"]', '<p>Test Basic Block with EDITED Test Content </p>')
            .submitForm('#edit-submit')
            .assert.containsText('body', 'Basic block Test Basic Block has been updated.', 'Successfully Edited Basic Block')

            // delete basic block
            .drupalRelativeURL('/block/1/delete')
            .submitForm('#block-content-basic-delete-form')
            .assert.containsText('body', 'The custom block Test Basic Block has been deleted.', 'Successfully Deleted Basic Block')

            //logout
            .drupalLogout()
            .end();
    }
}
