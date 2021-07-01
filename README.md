# jurassic.ninja

A frontend to launching ephemeral WordPress instances that auto-destroy after some time.

## Usage

### Requirements

* An [ubuntu box managed by ServerPilot](https://serverpilot.io/community/articles/connect-a-digitalocean-server.html).
* A [ServerPilot plan](https://serverpilot.io/community/articles/how-to-upgrade-your-account.html). One of the paid plans. Economy plan is OK. This is to take advantage of multiple sysusers management.
* [sshpass](https://linux.die.net/man/1/sshpass) installed on the box. (Just `apt-get install sshpass` after you have the Ubuntu box set up).
* **Have a domain name you fully control**.
    * Add a wildcard A record for every subdomain under that domain, pointing to the box's IP addresss.

### Installation

This software is implemented as a WordPress plugin that can be added on top of WordPress and configured via wp-admin.

On plugin activation, this plugin will create two tables on the same database your WordPress is running. Named `sites` and `purged`.


1. Install the plugin and activate it.
1. Create the needed pages (home and `/create`).
1. Configure ServerPilot credentials.

#### 1. Install the Plugin

* Get the bundle from GitHub.

    ```sh
    wp plugin install https://github.com/Automattic/jurassic.ninja/archive/master.zip
    ```

* Install composer dependencies

    ```sh
    cd wp-content/plugins/jurassic.ninja
    composer install --no-dev
    ```

* **Activate the plugin**

    ```sh
    wp plugin activate jurassic.ninja
    ```

#### 2. Create pages for launching the sites.

Your WordPress site will need a total of three pages in order for Jurassic Ninja to work with all its features:

##### Home

Create a new page and call it "Home". For the page's content the minimum needed is that it contains a link to the `/create` page. You can use the contents of [home.md](/docs/home.md) as a starting point.

In wp-admin go to `Settings > Reading` and configure the homepage as a static page and choose the page you just created called "Home".

##### Create

All the frontend site launching is done by a little piece of Javascript that detects if the current page is on the `/create` path. If this is the case, it launches a request in the background to this plugin's REST API in order to launch a new site.

Create a new page and call it "Create". Use `create` as the slug.

Add this content to it using the Text version of the editor:

```html
<img id="img1" class="aligncenter" style="display: none;" data-failure-img-src="https://i.imgur.com/vdyaxmx.gif" src="https://media.giphy.com/media/uIRyMKFfmoHyo/giphy.gif" />
<img id="img2" class="aligncenter" style="display: none;" src="https://i1.wp.com/media.giphy.com/media/KF3r4Q6YCtfOM/giphy.gif?ssl=1" />
<p id="progress" class="lead" style="text-align: center;" data-success-message="The new WP is ready to go, visit it!" data-error-message="Oh No! There was a problem launching the new WP.">Launching a fresh WP with a Jetpack ...</p>
```

##### Specialops

Create a new page and call it "Specialops". You can use the contents of [specialops.md](/docs/specialops.md) as a starting point.


#### 3. Configure Jurassic Ninja Settings page in wp-admin

1. Visit the Jurassic Ninja Settings page in wp-admin.
2. Configure your Server pilot credentials:
    - client id: You can get this from the Account Settings > API.
    - client key: Same as API Key. This is in the same page as the client id.
    - server id: When you click on a server, the last bit of the URL will be the sever id.

3. Configure the top-domain on which this is going to create sites.

### Using a Docker container for testing

It may be convenient to test this plugin using the [Jetpack monorepo Docker setup](https://github.com/Automattic/jetpack/blob/master/tools/docker/README.md).

Make sure that your copy of this repo and the Jetpack monorepo are stored within the same directory, and that [Docker is installed and running](https://docs.docker.com/install/) before proceeding.

1. Create `jndb` folder in the root of this source code, where WordPress DB will be stored:

```sh
mkdir ./jndb
```

2. create and run Docker containers:

```sh
docker-compose up
```

3. Navigate to <http://localhost> to create WordPress site, Activate Jurassic Ninja plugin. Other plugins are optional

4. Setup Jurassic Ninja plugin as mentioned above

### Gotchas

Because of the way ServerPilot handles PHP-FPM restarts, the PHP version you use for the Jurassic Ninja app cannot be used for any of the sites you plan to launch.
