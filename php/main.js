import * as v from './vanilla.js';

const click = v.event('click');
const inputEvent = v.event('input');
const inputValue = v.eventProperty('input', 'value');

const { type, value, selected } = v.attributes;

const { div, button, select, option } = v.tags;
const textInput = v.tag('input', (ob) => [inputValue(ob), type('text')]);
const textArea = v.tag('textarea', (ob) => [inputValue(ob)]);
const checkbox = v.tag('input', (ob) => [inputChecked(ob), type('checkbox')]);

const hidden = v.className('hidden');

const systemPrompt = "You are a helpful assistant but your answers are short like you are a robot. You don't omit useful information but you don't repeat the question and you don't use filler words or pleasantries and you don't try to be polite.";
const models = [
  'gpt-4-turbo-2024-04-09', 
  'gpt-3.5-turbo-0125',
  'test',
];
const model = new v.Observable(localStorage.gptModel || models[0]);
model.listen((v) => {
  localStorage.gptModel = v;
});
const maxTokens = 1000;

if (window.location.hash.length > 1) {
  localStorage.apiKey = window.location.hash.substring(1);
  window.location.hash = '';
}

function getApiKey() {
  return localStorage.apiKey;
}

async function askGpt(question) {
  if (!getApiKey()) {
    return "apiKey is missing";
  }
  const requestData = {
    model: model.value,
    messages: [
      { role: 'system', content: systemPrompt },
      { role: 'user', content: question }
    ],
    temperature: 0,
    max_tokens: maxTokens,
  };

  const apiUrl = "./api.php";

  const response = await fetch(apiUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${getApiKey()}`
    },
    body: JSON.stringify(requestData)
  });
  const data = await response.json();

  if (data.error?.message) {
    return data.error.message;
  }
  const answer = data.choices.map(choice => choice.message.content).join('\n');
  const cost = data.usage.cost;
  const newBudget = data.usage.new_budget;
  return {
    answer,
    cost,
    newBudget,
  };
}

window.askGpt = askGpt;

const q = new v.Observable('');
const budget = new v.Observable(0);
const isSending = new v.Observable(false);
const isNotSending = isSending.map(v => !v);

const messages = new v.ObservableArray([]);

const inputElement = textArea(q, v.classes('question'));
q.listen(() => {
  inputElement.style.height = 44;
  inputElement.style.height = Math.max(inputElement.scrollHeight + 4, 44);
});
inputElement.style.height = 44;

async function send() {
  isSending.value = true;
  try {
    const question = q.value;
    q.value = '';
    messages.push(div(v.classes('question'), question))
    const { answer, cost, newBudget } = await askGpt(question);
    budget.value = Math.floor(newBudget / 10000);
    messages.push(div(
      v.classes('answer'),
      answer,
      div(
        v.classes('cost'),
        'Cost: ',
        Math.ceil(cost / 10000),
      )
    ));
  } finally {
    isSending.value = false;
  }
  inputElement.focus();
}

async function loadBudget() {
  const budgetUrl = "./get_budget.php";
  const response = await fetch(budgetUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${getApiKey()}`
    },
  });
  const b = await response.json();
  budget.value = Math.floor(b / 10000);
}


v.body(
  div(
    v.classes('page'),
    div(
      'Model: ',
      select(
        inputValue(model),
        models.map(m => option(m, value(m), selected(m === model.value))),
      ),
    ),
    div('Budget: ', budget),
    messages,
    div(
      hidden(isSending),
      inputElement,
    ),
    div(
      hidden(isNotSending),
      v.classes('answer'),
      'Thinking...',
    ),
    div(
      button(
        'Ask',
        click(send),
      ),
    ),
  )
);

loadBudget();
inputElement.focus();
