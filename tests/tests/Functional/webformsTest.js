module.exports = {
    '@tags': ['contact','page'],
    'Testing the Contact Us webform' : function (browser) {
        browser.url(browser.launch_url)
            .drupalLogin('test','test')
            .drupalRelativeURL('/webform/contact/test')
            /*
            .setValue('input[name="subject"]', 'This is a subject')
            .click('id','edit-comments-and-feedback')
            .setValue('#edit-comments-and-feedback', "This is a comment")
            .setValue('input[id="edit-comments-and-feedback"]','This is a comment')
            .setValue('input[name="first_name"]', "First Name")
            .setValue('input[name="last_name"]', "Last Name")
            .setValue('input[name="organization"]', "Organization")
            .setValue('input[name="e_mail_address"]',"testemail@test123.com")
             */

            .setValue('select[name="consent"]', "Yes")

            .waitForElementVisible('#edit-actions-submit', 1000)

            .submitForm('#edit-actions-submit')
            .assert.containsText('body', 'Your message has been sent',"Contact Us Form Test Successful")

            .drupalLogout()
            .end();
    },

    'Testing the "suggest dataset" webform' : function (browser) {
        browser.url(browser.launch_url)
            .drupalLogin('test','test')
            .drupalRelativeURL('/webform/suggest_dataset/test')

            /*
            .setValue('#edit-name-of-dataset', 'DataSetName')
            .setValue('#edit-federal-government-institution', 'OrganizationName')
            .setValue('#edit-description-dataset','Dataset Description')
            .setValue('#edit-additional-comments-and-feedback',"This is a comment")
            .setValue('input[id="edit-last-name"]', 'Last Name')
            .setValue('input[id="edit-title"]', 'Title')
            .setValue('input[id="edit-organization"]', 'Organization')
            .setValue('input[id="edit-e-mail-address"]','testemail123@test123.com')
             */
            .setValue('select[id="edit-consent"]','Yes')
            .waitForElementVisible('#edit-actions-submit', 10000)


            .submitForm('id','edit-actions-submit')
            .assert.containsText('body', 'Thank you for your submission. Your request will be processed and added to the Suggested Dataset Catalogue as soon as possible.',"Dataset successfully suggested")

            .drupalLogout()
            .end();
    }

}
