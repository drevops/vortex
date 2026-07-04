@@ -59,6 +59,21 @@
     docker-php-ext-install pcntl && \
     apk del g++ make autoconf
 
+# Install the Lagoon CLI. Vortex wraps the provider-native CLI for hosting
+# database operations; baking it into this image makes those operations work
+# inside the Lagoon environment (where this image is the runtime), not just on
+# a developer host.
+ARG VORTEX_LAGOONCLI_VERSION=__VERSION__
+RUN arch="$(uname -m)" && \
+    case "${arch}" in \
+      x86_64) arch=amd64 ;; \
+      aarch64 | arm64) arch=arm64 ;; \
+      *) echo "Unsupported architecture: ${arch}" && exit 1 ;; \
+    esac && \
+    curl -fsSL --retry 3 --retry-delay 2 --max-time 60 -o /usr/local/bin/lagoon "https://github.com/uselagoon/lagoon-cli/releases/download/${VORTEX_LAGOONCLI_VERSION}/lagoon-cli-${VORTEX_LAGOONCLI_VERSION}-linux-${arch}" && \
+    chmod +x /usr/local/bin/lagoon && \
+    lagoon --version
+
 # Add patches and scripts.
 COPY patches /app/patches
 COPY scripts /app/scripts
