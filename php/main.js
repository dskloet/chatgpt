import * as v from './vanilla.js';

const click = v.event('click');
const inputEvent = v.event('input');
const value = v.property('value');
const inputValue = v.eventProperty('input', 'value');

const { type } = v.attributes;

const { div, button } = v.tags;
const textInput = v.tag('input', (ob) => [inputValue(ob), type('text')]);
const checkbox = v.tag('input', (ob) => [inputChecked(ob), type('checkbox')]);

const systemPrompt = "You are a helpful assistant but your answers are short like you are a robot. You don't omit useful information but you don't repeat the question and you don't use filler words or pleasantries and you don't try to be polite.";
const model = 'gpt-4-turbo-2024-04-09';
//const model = 'gpt-3.5-turbo-0125'
//const model = 'test'
const maxTokens = 1000;

const apiKey = window.location.hash.substring(1);

async function askGpt(question) {
  if (!apiKey) {
    return "apiKey is missing";
  }
  const requestData = {
    model,
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
      'Authorization': `Bearer ${apiKey}`
    },
    body: JSON.stringify(requestData)
  });
  const data = await response.json();

  if (data.error?.message) {
    return data.error.message;
  }
  const answer = data.choices.map(choice => choice.message.content).join('\n');
  return answer;
}

window.askGpt = askGpt;

const q = new v.Observable('');

const messages = new v.ObservableArray([]);

const inputElement = textInput(q);

async function send() {
  const question = q.value;
  q.value = '';
  messages.push(div(v.classes('question'), question))
  const answer = await askGpt(question);
  messages.push(div(v.classes('answer'), answer))
  inputElement.focus();
}

v.body(
  div(
    v.classes('page'),
    div('Model: ', model),
    messages,
    div(
      inputElement,
    ),
    div(
      button(
        'Ask',
        click(send),
      ),
    ),
  )
);

inputElement.focus();
