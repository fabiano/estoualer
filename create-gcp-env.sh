# Get the project id
GCP_PROJECT_ID=$(gcloud config get-value project)

# Set the variables
GCP_REGION=us-west1
GCP_ARTIFACT_REGISTRY_DOCKER_HOST=${GCP_REGION}-docker.pkg.dev
GCP_ARTIFACT_REGISTRY_DOCKER_REPOSITORY_NAME=docker
API_SERVICE_NAME=estoualer-api
API_SERVICE_IMAGE_NAME=${GCP_ARTIFACT_REGISTRY_DOCKER_HOST}/${GCP_PROJECT_ID}/${GCP_ARTIFACT_REGISTRY_DOCKER_REPOSITORY_NAME}/${API_SERVICE_NAME}
API_SERVICE_DOMAIN=api.estoualer.dev
WEBSITE_SERVICE_NAME=estoualer-website
WEBSITE_SERVICE_IMAGE_NAME=${GCP_ARTIFACT_REGISTRY_DOCKER_HOST}/${GCP_PROJECT_ID}/${GCP_ARTIFACT_REGISTRY_DOCKER_REPOSITORY_NAME}/${WEBSITE_SERVICE_NAME}
WEBSITE_SERVICE_DOMAIN=estoualer.dev

# Enable the Cloud Run & Artifact Registry services
gcloud services enable \
  run.googleapis.com \
  artifactregistry.googleapis.com

# Create the docker repository
gcloud artifacts repositories create ${GCP_ARTIFACT_REGISTRY_DOCKER_REPOSITORY_NAME} \
  --repository-format docker \
  --location ${GCP_REGION}

# Configure Docker to use the gcloud command-line tool as a credential helper
gcloud auth configure-docker ${GCP_ARTIFACT_REGISTRY_DOCKER_HOST}

# Build and push the images
docker build --tag ${API_SERVICE_IMAGE_NAME}:latest api
docker build --tag ${WEBSITE_SERVICE_IMAGE_NAME}:latest website
docker push ${API_SERVICE_IMAGE_NAME}:latest
docker push ${WEBSITE_SERVICE_IMAGE_NAME}:latest

# Deploy the services to Cloud Run
gcloud run deploy ${API_SERVICE_NAME} \
  --image ${API_SERVICE_IMAGE_NAME}:latest \
  --region ${GCP_REGION} \
  --allow-unauthenticated \
  --set-env-vars QUADRINHOS_API_KEY=${QUADRINHOS_API_KEY},QUADRINHOS_SPREADSHEET_ID=${QUADRINHOS_SPREADSHEET_ID}

gcloud run deploy ${WEBSITE_SERVICE_NAME} \
  --image ${WEBSITE_SERVICE_IMAGE_NAME}:latest \
  --region ${GCP_REGION} \
  --port 80 \
  --allow-unauthenticated

# Add the domain mapping
gcloud beta run domain-mappings create \
  --service ${API_SERVICE_NAME} \
  --domain ${API_SERVICE_DOMAIN} \
  --region ${GCP_REGION}

gcloud beta run domain-mappings create \
  --service ${WEBSITE_SERVICE_NAME} \
  --domain ${WEBSITE_SERVICE_DOMAIN} \
  --region ${GCP_REGION}
