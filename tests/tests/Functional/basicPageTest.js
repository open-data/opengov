module.exports = {
    '@tags': ['basic','page'],
    'basicPage' : function (browser) {
        browser.url(browser.launch_url)
        //login
            .drupalLogin('test','test')

            //create basic page
            .drupalRelativeURL('/node/add/page')
            .setValue('input[name="title[0][value]"]', 'TestBasicPage')
            .execute(
                function (instance, content) {
                    CKEDITOR.instances[instance].setData(content);
                },
                [
                    "edit-body-0-value",
                    "<p>Test Basic Page with Embedded Image </p>"
                ]
            )

            //preview before creation
            .click('id','edit-preview')
            .assert.urlContains('preview', 'Successfully Previewed Page')
            .click('id','edit-backlink')

            //save as draft
            .setValue('select[name="moderation_state[0][state]"]', 'draft')
            .submitForm('#edit-submit')
            .assert.urlEquals('http://127.0.0.1:8080/en/content/testbasicpage','Page Has Been Successfully Created as DRAFT')
            .click('link text','Edit')

            //save as in review
            .setValue('select[name="moderation_state[0][state]"]', 'in_review')
            .submitForm('#edit-submit')
            .assert.urlEquals('http://127.0.0.1:8080/en/content/testbasicpage','Page Has Been Successfully SAVED as IN REVIEW')
            .click('link text','Edit')

            //save as published
            .setValue('select[name="moderation_state[0][state]"]', 'published')
            .submitForm('#edit-submit')
            .assert.urlEquals('http://127.0.0.1:8080/en/content/testbasicpage','Page Has Been Successfully SAVED as PUBLISHED')
            .click('link text','Edit')


            //edit basic page

            .execute(
                function (instance, content) {
                    CKEDITOR.instances[instance].setData(content);
                },
                [
                    "edit-body-0-value",
                    "<p>Test Basic Page Edit </p>"
                ]
            )
            .submitForm('#edit-submit')
            .assert.urlEquals('http://127.0.0.1:8080/en/content/testbasicpage','Page Successfully Edited')

        //translate basic page
            .click('link text','Translate')
            .click('link text','Add')
            .execute(
                function (instance, content) {
                    CKEDITOR.instances[instance].setData(content);
                },
                [
                    "edit-body-0-value",
                    "<p>Test Basic Page Translation </p>"
                ]
            )
            .submitForm('#edit-submit')
            .assert.urlEquals('http://127.0.0.1:8080/fr/contenu/testbasicpage','Page Successfully Translated')

        //delete basic page and translation
            .click('link text','Supprimer')
            .submitForm('#edit-submit')
            .waitForElementVisible('body')
            .assert.containsText('body', 'La traduction en French du(de la) Page de base TestBasicPage a été supprimée.', 'Successfully DELETED')
            .click('link text','Delete')
            .submitForm('#edit-submit')

            .assert.containsText('body', 'The Basic page TestBasicPage has been deleted', 'Successfully DELETED')
            .assert.urlEquals('http://127.0.0.1:8080/en','ALL TESTS SUCCESSFUL')

        //logout
            .drupalLogout()
            .end();
    }
}
