#!/bin/bash
set -e

# get the project id
GCP_PROJECT_ID=$(gcloud config get-value project)

# set the variables
GCP_REGION=us-west1
GCP_ARTIFACT_REGISTRY_DOCKER_REPOSITORY_NAME=docker
WEBSITE_SERVICE_NAME=estoualer-website
WEBSITE_SERVICE_DOMAIN=estoualer.dev

# enable the Cloud Run & Artifact Registry services
gcloud services enable \
  run.googleapis.com \
  artifactregistry.googleapis.com

# create the docker repository
gcloud artifacts repositories create ${GCP_ARTIFACT_REGISTRY_DOCKER_REPOSITORY_NAME} \
  --repository-format docker \
  --location ${GCP_REGION}

# create the Cloud Run service using a dummy image
gcloud run deploy ${WEBSITE_SERVICE_NAME} \
  --image us-docker.pkg.dev/cloudrun/container/hello \
  --region ${GCP_REGION} \
  --allow-unauthenticated

gcloud beta run domain-mappings create \
  --service ${WEBSITE_SERVICE_NAME} \
  --domain ${WEBSITE_SERVICE_DOMAIN} \
  --region ${GCP_REGION}
