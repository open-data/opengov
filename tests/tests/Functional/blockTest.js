module.exports = {
    '@tags': ['basic', 'block'],
    'basicBlock' : function (browser) {
        browser.url(browser.launch_url)
            //login
            .drupalLogin('test','test')

            //create basic block
            .drupalRelativeURL('/block/add/basic')
            .setValue('input[name="info[0][value]"]', 'BasicBlockTest')
            .execute(
                function (instance, content) {
                    CKEDITOR.instances[instance].setData(content);
                },
                [
                    "edit-body-0-value",
                    "<p>Test Basic Block Page </p>"
                ]
            )
            .submitForm('#edit-submit')
            .assert.containsText('body', 'Basic block BasicBlockTest has been created.', 'Successfully Created Basic Block')

            // edit basic block
            .drupalRelativeURL('/admin/structure/block/block-content')
            .click('link text','BasicBlockTest')
            .execute(
                function (instance, content) {
                    CKEDITOR.instances[instance].setData(content);
                },
                [
                    "edit-body-0-value",
                    "<p>Test Basic Block Page Edit </p>"
                ]
            )
            .submitForm('#edit-submit')
            .assert.containsText('body', 'Basic block BasicBlockTest has been updated.', 'Successfully Edited Basic Block')

            // delete basic block
            .drupalRelativeURL('/admin/structure/block/block-content')
            .click('link text','BasicBlockTest')
            .click('link text', 'Delete')

            .submitForm('#edit-submit')
            .assert.containsText('body', 'The custom block BasicBlockTest has been deleted.', 'Successfully Deleted Basic Block')

            //logout
            .drupalLogout()
            .end();
    }
}
