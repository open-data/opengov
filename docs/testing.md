# Setting up Automated Testing using NightWatch


## 1. Install nodejs
```
    $ sudo yum install nodejs
    $ npm update
    $ sudo npm cache clean -f
    $ sudo npm install -g n
    $ sudo n stable
    $ sudo n latest
    $ yes |sudo  cp -rf /usr/local/bin/node /bin/node
```

## 2. Download latest version of selenium-standalone-server.jar and chromedriver to tests/bin
```
    $ cd /opt/tbs/wcms/open_government/tests/bin
    $ wget https://selenium-release.storage.googleapis.com/[VERSION]/selenium-server-standalone-[VERSION].
    - rename to selenium-server-standalone.jar
    $ wget https://chromedriver.storage.googleapis.com/[VERSION]/chromedriver_linux64.zip
    $ unzip chromedriver_linux64.zip
    $ rm chromedriver_linux64.zip
```

## 3. Install Nightwatch globally
```
    $ npm install -g nightwatch
```

## 4. Verify tests/nightwatch.json, specifically locations of the following:
```
        "server_path" : "./bin/selenium-server-standalone.jar",
        "webdriver.chrome.driver" : "./bin/chromedriver"
```

***default nightwatch.json is configured for TravisCI Tests. For local dev replace nightwatch.json with nightwatch_backup_for_local.json ***

## 5. Running Tests
```
    $ cd /location/to/nightwatch.js
    $ sudo nightwatch tests/[TESTNAME].js
```
To test if nightwatch is working, run
```
    $ sudo nightwatch tests/basic/homepageTest.js
```
This just tests if the text "Open Government" is present on homepage

## Full steps in order
```
    $ sudo yum install nodejs
    $ npm update
    $ sudo npm cache clean -f
    $ sudo npm install -g n
    $ sudo n stable
    $ sudo n latest
    $ yes |sudo  cp -rf /usr/local/bin/node /bin/node
    $ npm install -g nightwatch
    $ cd /opt/tbs/wcms/open_government/tests
    $ sudo nightwatch tests/Functional/BasicPage/masterBasicPageTest.js
```