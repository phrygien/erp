FROM ubuntu:latest
LABEL authors="mphrygien"

ENTRYPOINT ["top", "-b"]
