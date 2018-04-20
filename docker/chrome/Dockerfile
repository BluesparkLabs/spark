FROM alpine:edge

RUN apk --no-cache upgrade && apk add --no-cache chromium

EXPOSE 9222

ENTRYPOINT ["chromium-browser"]

# Taken from https://github.com/westy92/headless-chrome-alpine/blob/master/Dockerfile.
# New flags added: --disable-web-security, --user-data-dir, --remote-debugging-address.
CMD [ \
  # Disable various background network services, including extension updating,
  #   safe browsing service, upgrade detector, translate, UMA
  "--disable-background-networking", \
  # Disable installation of default apps on first run
  "--disable-default-apps", \
  # Disable all chrome extensions entirely
  "--disable-extensions", \
  # Disable the GPU hardware acceleration
  "--disable-gpu", \
  # Disable syncing to a Google account
  "--disable-sync", \
  # Disable built-in Google Translate service
  "--disable-translate", \
  # Disable cross-origin safeguards
  "--disable-web-security", \
  # Run in headless mode
  "--headless", \
  # Hide scrollbars on generated images/PDFs
  "--hide-scrollbars", \
  # Disable reporting to UMA, but allows for collection
  "--metrics-recording-only", \
  # Mute audio
  "--mute-audio", \
  # Skip first run wizards
  "--no-first-run", \
  # Disable sandbox mode
  "--no-sandbox", \
  # Expose port 9222 for remote debugging
  "--remote-debugging-port=9222", \
  # Set remote debugging address — important, otherwise 'localhost' would be used which breaks
  #   container linking and port expose.
  "--remote-debugging-address=0.0.0.0", \
  # Disable fetching safebrowsing lists, likely redundant due to disable-background-networking
  "--safebrowsing-disable-auto-update", \
  # Make use of user data directory
  "--user-data-dir" \
]
