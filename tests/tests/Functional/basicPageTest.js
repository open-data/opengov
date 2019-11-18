module.exports = {
    '@tags': ['basic','page'],
    'basicPage' : function (browser) {
        browser.url(browser.launch_url)
            //login
            .drupalLogin('test','test')

            //create basic page
            .drupalRelativeURL('/node/add/page')
            .setValue('input[name="title[0][value]"]', 'Test Basic Page')
            .setValue('textarea[name="body[0][value]"]', '<p>Test Basic Page with Test Content </p>')

            //preview before creation
            .click('id','edit-preview')
            .assert.urlContains('preview', 'Successfully Previewed Page')
            .click('id','edit-backlink')

            //save as draft
            .setValue('select[name="moderation_state[0][state]"]', 'draft')
            .submitForm('#node-page-form')
            .assert.urlEquals('http://127.0.0.1:8080/en/content/test-basic-page','Page Has Been Successfully Created as DRAFT')

            //save as in review
            .setValue('select[name="new_state"]', 'in_review')
            .submitForm('#content-moderation-entity-moderation-form')
            .assert.urlEquals('http://127.0.0.1:8080/en/content/test-basic-page','Page Has Been Successfully SAVED as IN REVIEW')

            //save as published
            .setValue('select[name="new_state"]', 'published')
            .submitForm('#content-moderation-entity-moderation-form')
            .assert.urlEquals('http://127.0.0.1:8080/en/content/test-basic-page','Page Has Been Successfully SAVED as PUBLISHED')

            //edit basic page
            .drupalRelativeURL('/node/1/edit')
            .setValue('textarea[name="body[0][value]"]', '<p>Test Basic Page with EDITED Test Content </p>')
            .submitForm('#node-page-edit-form')
            .assert.urlEquals('http://127.0.0.1:8080/en/content/test-basic-page','Page Successfully Edited')

            //translate basic page
            .drupalRelativeURL('/node/1/translations')
            .click('link text','Add')
            .setValue('textarea[name="body[0][value]"]', '<p>Test Basic Page with TRANSLATED Test Content </p>')
            .submitForm('#node-page-form')
            .assert.urlEquals('http://127.0.0.1:8080/fr/contenu/test-basic-page','Page Successfully Translated')

            //delete basic page and translation
            .drupalRelativeURL('/node/1/delete')
            .submitForm('#node-page-delete-form')
            .waitForElementVisible('body')
            .assert.containsText('body', 'The Basic page Test Basic Page has been deleted', 'Successfully DELETED')
            .assert.urlEquals('http://127.0.0.1:8080/en','ALL TESTS SUCCESSFUL')

            //logout
            .drupalLogout()
            .end();
    }
}
