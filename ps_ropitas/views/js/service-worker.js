var CACHE_NAME = 'ropitas-cache-v1.5.1';

var urlsToCache = [
  '/'
];

self.addEventListener('install', function(event) {
  // Perform install steps
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});
self.addEventListener('fetch', function(event) {
  if (event.request.method !== 'GET') {
    return;
  }
  var shouldExclude = true;
  if (/\.jpg|\.png|\.gif|\.css|\.js|\.eot|\.svg|\.ttf|\.woff|\.html/i
        .test(event.request.url)) {
    shouldExclude = false;
  } else if (/css\?/.test(event.request.url)) {
    shouldExclude = false;
  } else if (/\/\d+?\-[a-zA-Z0-9\-]+?$/.test(event.request.url.split('?')[0].split('#')[0]) &&
      event.request.url.indexOf('register') === -1) {
    shouldExclude = false;
  } else if (/ropitas\.com\.ar\/?$/.test(event.request.url.split('?')[0].split('#')[0])) {
    shouldExclude = false;
  } else if (/shipping\.php|socialrating/.test(event.request.url)) {
    shouldExclude = false;
  }
  if (/service-worker\.js/.test(event.request.url)) {
    shouldExclude = true;
  }
  if (shouldExclude) {
    return;
  }
  event.respondWith(
    caches.match(event.request)
      .then(function(cached) {
        var unableToResolve = function() {
          /* There's a couple of things we can do here.
             - Test the Accept header and then return one of the `offlineFundamentals`
               e.g: `return caches.match('/some/cached/image.png')`
             - You should also consider the origin. It's easier to decide what
               "unavailable" means for requests against your origins than for requests
               against a third party, such as an ad provider
             - Generate a Response programmaticaly, as shown below, and return that
          */

          console.log('WORKER: fetch request failed in both cache and network.');

          /* Here we're creating a response programmatically. The first parameter is the
             response body, and the second one defines the options for the response.
          */
          return new Response('<body><h1 style="text-align: center;">Conexión perdida</h1><button onclick="location.replace(location.href)" style="width: 100%; font-size: x-large;">REINTENTAR</button></body>', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
              'Content-Type': 'text/html'
            })
          });
        };

        var fetchedFromNetwork = function(response) {
          // Check if we received a valid response
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // IMPORTANT: Clone the response. A response is a stream
          // and because we want the browser to consume the response
          // as well as the cache consuming the response, we need
          // to clone it so we have two streams.
          var responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then(function(cache) {
              cache.put(event.request, responseToCache);
            });

          return response;
        };

        /*
        // Cache hit - return response
        if (response) {
          return response;
        }
        */
        var networked = fetch(event.request)
            // We handle the network request with success and failure scenarios.
            .then(fetchedFromNetwork, unableToResolve)
            // We should catch errors on the fetchedFromNetwork handler as well.
            .catch(unableToResolve);

        console.log('WORKER: fetch event', cached ? '(cached)' : '(network)', event.request.url);
        return cached || networked;

      })
    );
});

/* The activate event fires after a service worker has been successfully installed.
   It is most useful when phasing out an older version of a service worker, as at
   this point you know that the new worker was installed correctly. In this example,
   we delete old caches that don't match the version in the worker we just finished
   installing.
*/
self.addEventListener("activate", function(event) {
  /* Just like with the install event, event.waitUntil blocks activate on a promise.
     Activation will fail unless the promise is fulfilled.
  */

  event.waitUntil(
    caches
      /* This method returns a promise which will resolve to an array of available
         cache keys.
      */
      .keys()
      .then(function (keys) {
        // We return a promise that settles when all outdated caches are deleted.
        return Promise.all(
          keys
            .filter(function (key) {
              // Filter by keys that don't start with the latest version prefix.
              return !key.startsWith(CACHE_NAME);
            })
            .map(function (key) {
              /* Return a promise that's fulfilled
                 when each outdated cache is deleted.
              */
              return caches.delete(key);
            })
        );
      })
      .then(function() {
      })
  );
});
