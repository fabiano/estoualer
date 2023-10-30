#!/bin/bash
set -e

# Get the project id
GCP_PROJECT_ID=$(gcloud config get-value project)

# Set the variables
GCP_REGION=us-west1
GCP_ARTIFACT_REGISTRY_DOCKER_REPOSITORY_NAME=docker
API_SERVICE_NAME=estoualer-api
API_SERVICE_DOMAIN=api.estoualer.dev
WEBSITE_SERVICE_NAME=estoualer-website
WEBSITE_SERVICE_DOMAIN=estoualer.dev

# Enable the Cloud Run & Artifact Registry services
gcloud services enable \
  run.googleapis.com \
  artifactregistry.googleapis.com

# Create the docker repository
gcloud artifacts repositories create ${GCP_ARTIFACT_REGISTRY_DOCKER_REPOSITORY_NAME} \
  --repository-format docker \
  --location ${GCP_REGION}

# Create the Cloud Run services using a dummy image
gcloud run deploy ${API_SERVICE_NAME} \
  --image us-docker.pkg.dev/cloudrun/container/hello \
  --region ${GCP_REGION} \
  --allow-unauthenticated

gcloud beta run domain-mappings create \
  --service ${API_SERVICE_NAME} \
  --domain ${API_SERVICE_DOMAIN} \
  --region ${GCP_REGION}

gcloud run deploy ${WEBSITE_SERVICE_NAME} \
  --image us-docker.pkg.dev/cloudrun/container/hello \
  --region ${GCP_REGION} \
  --allow-unauthenticated

gcloud beta run domain-mappings create \
  --service ${WEBSITE_SERVICE_NAME} \
  --domain ${WEBSITE_SERVICE_DOMAIN} \
  --region ${GCP_REGION}
