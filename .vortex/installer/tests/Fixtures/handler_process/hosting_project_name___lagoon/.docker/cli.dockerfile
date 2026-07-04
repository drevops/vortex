@@ -59,6 +59,28 @@
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
+    base="https://github.com/uselagoon/lagoon-cli/releases/download/${VORTEX_LAGOONCLI_VERSION}" && \
+    asset="lagoon-cli-${VORTEX_LAGOONCLI_VERSION}-linux-${arch}" && \
+    curl -fsSL --retry 3 --retry-delay 2 --max-time 60 -o /tmp/lagoon "${base}/${asset}" && \
+    curl -fsSL --retry 3 --retry-delay 2 --max-time 60 -o /tmp/lagoon-checksums.txt "${base}/checksums.txt" && \
+    expected="$(awk -v a="${asset}" '$2 == a { print $1 }' /tmp/lagoon-checksums.txt)" && \
+    actual="$(sha256sum /tmp/lagoon)" && \
+    [ -n "${expected}" ] && [ "${expected}" = "${actual%% *}" ] && \
+    chmod +x /tmp/lagoon && \
+    mv /tmp/lagoon /usr/local/bin/lagoon && \
+    rm /tmp/lagoon-checksums.txt
+
 # Add patches and scripts.
 COPY patches /app/patches
 COPY scripts /app/scripts
