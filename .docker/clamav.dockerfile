# ClamAV container.
#
# @see https://hub.docker.com/r/clamav/clamav/tags
#
# Allow running ClamAV in rootless mode.
# @see https://github.com/Cisco-Talos/clamav/issues/478
#
# The `USER` names below are defined by the base images. Their numeric ids are
# an implementation detail of those images and would break on a base image
# bump, so DL3066 is skipped.
#
# hadolint global ignore=DL3008,DL3018,DL3066
#
# @see https://hub.docker.com/r/uselagoon/commons/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/commons

FROM uselagoon/commons:26.8.0 AS commons

FROM clamav/clamav-debian:1.5.3

COPY --from=commons /lagoon /lagoon
COPY --from=commons /bin/fix-permissions /bin/ep /bin/docker-sleep /bin/wait-for /bin/

RUN apt-get update -qq && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends tzdata && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

COPY .docker/config/clamav/clamav.conf /tmp/clamav.conf

RUN cat /tmp/clamav.conf >> /etc/clamav/clamd.conf && \
    rm /tmp/clamav.conf && \
    sed -i "s/^LogFile /# LogFile /g" /etc/clamav/clamd.conf && \
    sed -i "s/^#LogSyslog /LogSyslog /g" /etc/clamav/clamd.conf && \
    sed -i "s/^UpdateLogFile /# UpdateLogFile /g" /etc/clamav/freshclam.conf && \
    sed -i "s/^#LogSyslog /LogSyslog /g" /etc/clamav/freshclam.conf

USER root

RUN fix-permissions /var/lib/clamav

USER clamav

ENTRYPOINT [ "/init-unprivileged" ]
