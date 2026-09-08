const express = require('express');
const swaggerUi = require('swagger-ui-express');
const YAML = require('yamljs');
const path = require('path');

const app = express();
const swaggerDocument = YAML.load(path.join(__dirname, 'openapi.yaml'));

app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(swaggerDocument));

const PORT = 3001;
app.listen(PORT, () => {
  console.log(`Documentación Swagger disponible en http://localhost:${PORT}/api-docs`);
});
