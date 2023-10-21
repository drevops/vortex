# Pygmy

https://pygmy.readthedocs.io/

https://github.com/pygmystack/pygmy

> `pygmy` is the single tool needed to get the local amazee.io Docker Drupal
> Development Environment running on your Linux based system. It built to work
> with Docker for Mac! (quite a lot for such a small whale 🐳)

**What `pygmy` will handle for you:**

* An HTTP reverse proxy for nice URLs and HTTPS offloading.
* A DNS system so we don't have to remember IP addresses.
* SSH agents to use SSH keys within containers.
* A system that receives and displays mail locally available at http://mailhog.docker.amazee.io/.

## Installation

Please follow the [official installation instructions](https://github.com/pygmystack/pygmy/tree/main#installation).

## Usage

```shell
pygmy up       # Start the services. Required to run only once per system boot.
pygmy down     # Stop the services.
pygmy restart  # Restart the services.
pygmy status   # Show the status of the services.
pygmy clean    # Stop and remove all services. Useful if you have issues with pygmy.
```
